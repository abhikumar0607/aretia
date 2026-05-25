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
        'reference', 'order_id', 'company_id', 'workflow_stage_id',
        'assigned_to', 'assigned_by', 'assigned_at', 'status',
    ];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    /** @param  array<int, int|string>  $analystIds */
    public function syncAnalystTeam(array $analystIds, int $leadAnalystId, int $assignedBy): void
    {
        $ids = collect($analystIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if (! $ids->contains($leadAnalystId)) {
            $ids->prepend($leadAnalystId);
        }

        $this->analysts()->sync($ids->all());

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
            return $this->assignee->name;
        }

        return $team->pluck('name')->join(', ');
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

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function report(): HasMany
    {
        return $this->hasMany(Report::class, 'case_id');
    }

    public function latestReport()
    {
        return $this->hasOne(Report::class, 'case_id')->latestOfMany();
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

    /** Whether the viewer can use case chat (requires an assigned analyst). */
    public function isChatAvailableFor(?User $viewer = null): bool
    {
        if (! $this->assigned_to) {
            return false;
        }

        $viewer ??= auth()->user();
        if (! $viewer) {
            return false;
        }

        if ($viewer->hasRole(UserRole::Client)) {
            return (int) $this->company_id === (int) $viewer->company_id;
        }

        if ($viewer->hasRole(UserRole::Analyst)) {
            return $this->hasAnalyst($viewer);
        }

        return false;
    }

    /** Client contact for analyst/admin, or assigned analyst for client. */
    public function chatPartnerFor(User $viewer): ?User
    {
        if ($viewer->hasRole(UserRole::Analyst) || $viewer->hasRole(UserRole::Admin) || $viewer->hasRole(UserRole::SuperAdmin)) {
            return $this->resolvedClient();
        }

        return $this->isChatAvailableFor($viewer) ? $this->assignee : null;
    }
}
