<?php

namespace App\Services;

use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use App\Notifications\CaseTeamAssignedNotification;
use App\Support\DueDateRules;
use Carbon\Carbon;
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
            $isRequired = $type === EmployeeType::Analyst;
            $rules["team.{$type->value}"] = $isRequired
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'];
            $rules["team.{$type->value}.*"] = ['integer', 'exists:users,id'];
        }

        $messages = [
            'team.required' => 'Analyst is required.',
            'team.analyst.required' => 'Analyst is required.',
            'team.analyst.min' => 'Analyst is required.',
        ];

        $validator = validator(['team' => $team], $rules, $messages);

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
                if ($type === EmployeeType::Analyst) {
                    throw ValidationException::withMessages([
                        "team.{$type->value}" => 'Analyst is required. Assign an Analyst before QA or FQA.',
                    ]);
                }

                $membersByType->put($type->value, []);

                continue;
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
        if ($allIds->isNotEmpty() && $allIds->unique()->count() !== $allIds->count()) {
            throw ValidationException::withMessages([
                'team' => 'Each team member can only have one role on this case.',
            ]);
        }

        /** @var array<string, array<int, int>> $result */
        $result = $membersByType->all();

        return $result;
    }

    /**
     * @param  array<string, array<int, int>>  $memberIdsByType
     * @return array<string, string>
     */
    public function validateDueDates(array $memberIdsByType, array $dueDates): array
    {
        $rules = [];

        foreach (EmployeeType::cases() as $type) {
            $hasMembers = ! empty($memberIdsByType[$type->value]);
            if ($type === EmployeeType::Analyst || $hasMembers) {
                $rules["due_dates.{$type->value}"] = DueDateRules::required();
            }
        }

        $messages = [];
        foreach (EmployeeType::cases() as $type) {
            $messages["due_dates.{$type->value}.after_or_equal"] =
                'The '.$type->label().' due date must be today or a future date.';
        }

        $validator = validator(['due_dates' => $dueDates], $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array<string, string> */
        return collect($validator->validated()['due_dates'] ?? [])
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
    }

    /**
     * @param  array<string, array<int, int>>  $memberIdsByType
     * @param  array<string, string>  $dueDatesByRole
     */
    public function assign(
        CaseFile $case,
        array $memberIdsByType,
        int $assignedBy,
        array $dueDatesByRole,
    ): Collection {
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

        $dueDatesByUserId = [];
        foreach (EmployeeType::cases() as $type) {
            $roleDue = $dueDatesByRole[$type->value] ?? null;
            if (! $roleDue) {
                continue;
            }

            foreach ($memberIdsByType[$type->value] ?? [] as $userId) {
                $dueDatesByUserId[(int) $userId] = $roleDue;
            }
        }

        $allIds = collect($memberIdsByType)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $case->syncAnalystTeam($allIds, $leadId, $assignedBy, $dueDatesByUserId);

        $team = User::query()
            ->whereIn('id', $allIds)
            ->orderBy('name')
            ->get();

        $case->refresh();
        $leadId = (int) $case->assigned_to;
        $assignerLabel = User::query()->find($assignedBy)?->displayNameWithRole();

        $case->loadMissing(['company', 'order.package', 'analysts']);

        foreach ($team as $member) {
            try {
                $member->refresh();
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
