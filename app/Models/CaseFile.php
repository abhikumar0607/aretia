<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class CaseFile extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'reference', 'order_id', 'company_id', 'case_link_group_id', 'workflow_stage_id',
        'assigned_to', 'assigned_by', 'assigned_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function portalDueDate(?User $viewer = null): ?\Illuminate\Support\Carbon
    {
        return $this->employeeDueDateFor($viewer ?? auth()->user())
            ?? ($viewer?->isEmployee() ? null : $this->order?->due_date);
    }

    public function employeeDueDateFor(User|int|null $user): ?\Illuminate\Support\Carbon
    {
        if (! $user) {
            return null;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;

        if (! $this->hasAnalyst($userId)) {
            return null;
        }

        $member = $this->relationLoaded('analysts')
            ? $this->analysts->firstWhere('id', $userId)
            : $this->analysts()->where('users.id', $userId)->first();

        $dueDate = $member?->pivot?->due_date;

        return $dueDate ? \Illuminate\Support\Carbon::parse($dueDate) : null;
    }

    /**
     * @return array<string, string>
     */
    public function teamDueDatesByRole(): array
    {
        $team = $this->relationLoaded('analysts')
            ? $this->analysts
            : $this->analysts()->get();

        $dates = [];
        foreach ($team as $member) {
            $role = $member->role->value;
            if ($member->pivot->due_date && ! isset($dates[$role])) {
                $dates[$role] = \Illuminate\Support\Carbon::parse($member->pivot->due_date)->format('Y-m-d');
            }
        }

        return $dates;
    }

    public function portalDueDateLabel(?User $viewer = null): string
    {
        return $this->portalDueDate($viewer)?->format('d M Y') ?? 'TBD';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function linkGroup(): BelongsTo
    {
        return $this->belongsTo(CaseLinkGroup::class, 'case_link_group_id');
    }

    public function hasRelatedCases(): bool
    {
        if (! $this->case_link_group_id) {
            return false;
        }

        return CaseFile::query()
            ->where('case_link_group_id', $this->case_link_group_id)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** All analysts assigned to this case (team). */
    public function analysts(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'case_analyst', 'case_id', 'user_id')
            ->withPivot('due_date')
            ->withTimestamps()
            ->orderBy('users.name');
    }

    public function hasAnalyst(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        if ($this->relationLoaded('analysts')) {
            return $this->analysts->contains('id', $userId);
        }

        return $this->analysts()->where('users.id', $userId)->exists()
            || (int) $this->assigned_to === (int) $userId;
    }

    /**
     * @param  array<int, int|string>  $analystIds
     * @param  array<int, string>  $dueDatesByUserId
     */
    public function syncAnalystTeam(
        array $analystIds,
        int $leadAnalystId,
        int $assignedBy,
        array $dueDatesByUserId = [],
    ): void {
        $ids = collect($analystIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if (! $ids->contains($leadAnalystId)) {
            $ids->prepend($leadAnalystId);
        }

        $syncPayload = [];
        foreach ($ids as $userId) {
            $payload = [];
            if (! empty($dueDatesByUserId[$userId])) {
                $payload['due_date'] = $dueDatesByUserId[$userId];
            }
            $syncPayload[$userId] = $payload;
        }

        $this->analysts()->sync($syncPayload);

        $this->update([
            'assigned_to' => $leadAnalystId,
            'assigned_by' => $assignedBy,
            'assigned_at' => now(),
        ]);
    }

    public function scopeForAnalyst(Builder $query, User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $query->where(function (Builder $q) use ($userId) {
            $q->where('assigned_to', $userId)
                ->orWhereHas('analysts', fn (Builder $aq) => $aq->where('users.id', $userId));
        });
    }

    public function analystTeamNames(): string
    {
        $team = $this->relationLoaded('analysts')
            ? $this->analysts
            : $this->analysts()->get();

        if ($team->isEmpty() && $this->assignee) {
            return $this->assignee->displayNameWithRole();
        }

        return $team
            ->sortBy(fn (User $user) => $user->role->value)
            ->map(fn (User $user) => $user->displayNameWithRole())
            ->join(', ');
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, User>>
     */
    public function teamByEmployeeType(): array
    {
        $team = $this->relationLoaded('analysts')
            ? $this->analysts
            : $this->analysts()->get();

        $byType = [];
        foreach (\App\Enums\EmployeeType::cases() as $type) {
            $byType[$type->value] = $team->where('role', \App\Enums\UserRole::from($type->value))->values();
        }

        return $byType;
    }

    public function hasFullEmployeeTeam(): bool
    {
        foreach (\App\Enums\EmployeeType::cases() as $type) {
            $byType = $this->teamByEmployeeType();
            if (($byType[$type->value] ?? collect())->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(CaseStageHistory::class, 'case_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'case_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class, 'case_id')->oldest();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable')->latest();
    }

    public function documentsForViewer(?User $viewer = null): \Illuminate\Support\Collection
    {
        $viewer ??= auth()->user();

        $documents = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->with('uploader')->get();

        if ($documents->isNotEmpty() && ! $documents->first()->relationLoaded('uploader')) {
            $documents->load('uploader');
        }

        $documents = $documents->sortByDesc('created_at')->values();

        if ($viewer?->hasRole(UserRole::Client)) {
            return $documents
                ->filter(fn (Document $doc) => $doc->isVisibleToClient())
                ->values();
        }

        return $documents;
    }

    public function report(): HasMany
    {
        return $this->hasMany(Report::class, 'case_id');
    }

    public function latestReport()
    {
        return $this->hasOne(Report::class, 'case_id')->latestOfMany();
    }

    public function visibleStageLabel(?User $viewer = null): string
    {
        $viewer ??= auth()->user();

        if ($viewer?->hasRole(UserRole::Client)) {
            return \App\Support\CaseWorkflow::clientStageLabel($this->stage?->slug);
        }

        return $this->stage?->name ?? '—';
    }

    public function visibleStageColor(?User $viewer = null): string
    {
        $viewer ??= auth()->user();

        if ($viewer?->hasRole(UserRole::Client)) {
            return \App\Support\CaseWorkflow::clientStageColor($this->stage?->slug);
        }

        return $this->stage?->color ?? '#094FA4';
    }

    /**
     * Client-facing milestone history (internal QA/FQA steps hidden).
     *
     * @return \Illuminate\Support\Collection<int, object{label: string, color: string, updated_at: \Illuminate\Support\Carbon}>
     */
    public function clientVisibleStageHistories(): \Illuminate\Support\Collection
    {
        $histories = $this->relationLoaded('stageHistories')
            ? $this->stageHistories->sortBy('created_at')->values()
            : $this->stageHistories()->with('stage')->orderBy('created_at')->get();

        $entries = collect();
        $lastClientSlug = null;

        foreach ($histories as $history) {
            $clientSlug = \App\Support\CaseWorkflow::clientStageSlug($history->stage?->slug);

            if ($clientSlug === $lastClientSlug) {
                $entries->last()->updated_at = $history->created_at;

                continue;
            }

            $entries->push((object) [
                'label' => \App\Support\CaseWorkflow::clientStageLabel($history->stage?->slug),
                'color' => \App\Support\CaseWorkflow::clientStageColor($history->stage?->slug),
                'updated_at' => $history->created_at,
            ]);
            $lastClientSlug = $clientSlug;
        }

        return $entries;
    }

    public static function generateReference(): string
    {
        return 'CASE-'.strtoupper(uniqid());
    }

    /** Primary client user for this case (company primary contact or order placer). */
    public function primaryClient(): ?User
    {
        return User::query()
            ->where('company_id', $this->company_id)
            ->where('role', UserRole::Client)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first()
            ?? $this->order?->user;
    }

    /** Same as primaryClient() but uses eager-loaded relations when available. */
    public function resolvedClient(): ?User
    {
        if ($this->relationLoaded('company') && $this->company->relationLoaded('clientUsers')) {
            $client = $this->company->clientUsers->first();
            if ($client) {
                return $client;
            }
        }

        if ($this->relationLoaded('order') && $this->order?->relationLoaded('user')) {
            $orderUser = $this->order->user;
            if ($orderUser && $orderUser->hasRole(UserRole::Client)) {
                return $orderUser;
            }
        }

        return $this->primaryClient();
    }

    /** @return array<int, string> */
    public static function clientContactWith(): array
    {
        return [
            'company.clientUsers' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
            'order.user',
        ];
    }

    /** Whether the viewer can use case chat. */
    public function isChatAvailableFor(?User $viewer = null): bool
    {
        $viewer ??= auth()->user();

        return $viewer && app(\App\Services\CaseChatService::class)->canUseCaseChat($this, $viewer);
    }

    public function canUseCaseChat(?User $viewer = null): bool
    {
        return $this->isChatAvailableFor($viewer);
    }
}
