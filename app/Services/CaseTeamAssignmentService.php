<?php

namespace App\Services;

use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use App\Notifications\CaseTeamAssignedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CaseTeamAssignmentService
{
    /**
     * @return array<string, array<int, int>> type value => list of user ids
     */
    public function validateTeamPayload(array $team): array
    {
        $rules = ['team' => ['required', 'array']];
        foreach (EmployeeType::cases() as $type) {
            $rules["team.{$type->value}"] = ['required', 'array', 'min:1'];
            $rules["team.{$type->value}.*"] = ['integer', 'exists:users,id'];
        }

        $validator = validator(['team' => $team], $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated()['team'];
        $membersByType = collect();

        foreach (EmployeeType::cases() as $type) {
            $role = UserRole::from($type->value);
            $ids = collect($validated[$type->value] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                throw ValidationException::withMessages([
                    "team.{$type->value}" => 'Select at least one '.$type->label().'.',
                ]);
            }

            $validCount = User::employees()
                ->where('is_active', true)
                ->where('role', $role)
                ->whereIn('id', $ids->all())
                ->count();

            if ($validCount !== $ids->count()) {
                throw ValidationException::withMessages([
                    "team.{$type->value}" => 'All selected '.$type->label().' members must be active '.$type->label().' accounts.',
                ]);
            }

            $membersByType->put($type->value, $ids->all());
        }

        $allIds = $membersByType->flatten()->values();
        if ($allIds->unique()->count() !== $allIds->count()) {
            throw ValidationException::withMessages([
                'team' => 'Analyst, QA, and FQA members must be different people.',
            ]);
        }

        /** @var array<string, array<int, int>> $result */
        $result = $membersByType->all();

        return $result;
    }

    /**
     * @param  array<string, array<int, int>>  $memberIdsByType
     */
    public function assign(CaseFile $case, array $memberIdsByType, int $assignedBy): Collection
    {
        $analystIds = collect($memberIdsByType[EmployeeType::Analyst->value] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($analystIds->isEmpty()) {
            throw ValidationException::withMessages([
                'team.analyst' => 'Select at least one Analyst.',
            ]);
        }

        $leadId = (int) $case->assigned_to;
        if (! $analystIds->contains($leadId)) {
            $leadId = (int) $analystIds->first();
        }

        $allIds = collect($memberIdsByType)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $case->syncAnalystTeam($allIds, $leadId, $assignedBy);

        $team = User::query()
            ->whereIn('id', $allIds)
            ->orderBy('name')
            ->get();

        $case->refresh();
        $leadId = (int) $case->assigned_to;
        $assignerLabel = User::query()->find($assignedBy)?->displayNameWithRole();

        $case->loadMissing(['company', 'order.package']);

        foreach ($team as $member) {
            try {
                $member->notify(new CaseTeamAssignedNotification(
                    $case,
                    isLead: (int) $member->id === $leadId,
                    assignedByName: $assignerLabel,
                ));
            } catch (\Throwable $e) {
                Log::error('Case assignment notification failed', [
                    'case_id' => $case->id,
                    'user_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $team;
    }
}
