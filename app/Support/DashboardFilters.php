<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class DashboardFilters
{
    public const PERIOD_ALL = 'all';

    public const PERIOD_THIS_MONTH = 'this_month';

    public const PERIOD_CUSTOM = 'custom';

    public function __construct(
        public string $period = self::PERIOD_ALL,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?int $teamUserId = null,
        public ?int $companyId = null,
    ) {}

    public static function fromRequest(Request $request, ?string $namespace = null): self
    {
        $namespace ??= auth()->check() ? auth()->user()->role->value : 'guest';
        $sessionKey = 'dashboard_filters.'.$namespace;

        $hasFilterInput = $request->has('period')
            || $request->has('date_from')
            || $request->has('date_to')
            || $request->has('team_user')
            || $request->has('company_id');

        if (! $hasFilterInput && $request->session()->has($sessionKey)) {
            return self::fromSessionArray((array) $request->session()->get($sessionKey, []));
        }

        $filters = self::fromRequestQuery($request);

        $request->session()->put($sessionKey, [
            'period' => $filters->period,
            'date_from' => $filters->dateFrom,
            'date_to' => $filters->dateTo,
            'team_user' => $filters->teamUserId,
            'company_id' => $filters->companyId,
        ]);

        return $filters;
    }

    /** Listing pages: read period from query string only (no session). */
    public static function fromRequestQuery(Request $request): self
    {
        if ($request->has('period')) {
            $period = self::normalizePeriod((string) $request->input('period', self::PERIOD_ALL));
            $dateFrom = null;
            $dateTo = null;

            if ($period === self::PERIOD_CUSTOM) {
                $dateFrom = self::parseDate($request->input('date_from'));
                $dateTo = self::parseDate($request->input('date_to'));
            }

            return new self(
                period: $period,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                teamUserId: self::parseTeamUserId($request->input('team_user')),
                companyId: self::parseCompanyId($request->input('company_id')),
            );
        }

        if ($request->filled('due_from') || $request->filled('due_to')) {
            return new self(
                period: self::PERIOD_CUSTOM,
                dateFrom: self::parseDate($request->input('due_from')),
                dateTo: self::parseDate($request->input('due_to')),
                teamUserId: self::parseTeamUserId($request->input('team_user')),
                companyId: self::parseCompanyId($request->input('company_id')),
            );
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            return new self(
                period: self::PERIOD_CUSTOM,
                dateFrom: self::parseDate($request->input('date_from')),
                dateTo: self::parseDate($request->input('date_to')),
                teamUserId: self::parseTeamUserId($request->input('team_user')),
                companyId: self::parseCompanyId($request->input('company_id')),
            );
        }

        return new self(
            teamUserId: self::parseTeamUserId($request->input('team_user')),
            companyId: self::parseCompanyId($request->input('company_id')),
        );
    }

    /**
     * @param  array<string, mixed>  $saved
     */
    private static function fromSessionArray(array $saved): self
    {
        $period = self::normalizePeriod((string) ($saved['period'] ?? self::PERIOD_ALL));

        if ($period === self::PERIOD_ALL && (! empty($saved['date_from']) || ! empty($saved['date_to']))) {
            $period = self::PERIOD_CUSTOM;
        }

        return new self(
            period: $period,
            dateFrom: $period === self::PERIOD_CUSTOM ? self::parseDate($saved['date_from'] ?? null) : null,
            dateTo: $period === self::PERIOD_CUSTOM ? self::parseDate($saved['date_to'] ?? null) : null,
            teamUserId: self::parseTeamUserId($saved['team_user'] ?? null),
            companyId: self::parseCompanyId($saved['company_id'] ?? null),
        );
    }

    private static function normalizePeriod(string $period): string
    {
        return in_array($period, [self::PERIOD_ALL, self::PERIOD_THIS_MONTH, self::PERIOD_CUSTOM], true)
            ? $period
            : self::PERIOD_ALL;
    }

    /**
     * @return array<string, string>
     */
    public function toQueryArray(): array
    {
        if ($this->isDefault()) {
            return [];
        }

        $params = ['period' => $this->period];

        if ($this->period === self::PERIOD_CUSTOM) {
            if ($this->dateFrom) {
                $params['date_from'] = $this->dateFrom;
            }
            if ($this->dateTo) {
                $params['date_to'] = $this->dateTo;
            }
        }

        if ($this->teamUserId) {
            $params['team_user'] = (string) $this->teamUserId;
        }

        if ($this->companyId) {
            $params['company_id'] = (string) $this->companyId;
        }

        return $params;
    }

    public function isDefault(): bool
    {
        return $this->period === self::PERIOD_ALL
            && $this->teamUserId === null
            && $this->companyId === null;
    }

    public function hasScopeFilters(): bool
    {
        return $this->teamUserId !== null || $this->companyId !== null;
    }

    /**
     * Query params for the cases listing page (preserves dashboard filters).
     *
     * @return array<string, string>
     */
    public function toCasesListingQueryArray(int|string|null $stage = null): array
    {
        $params = $this->toQueryArray();

        if ($this->companyId) {
            unset($params['company_id']);
            $params['company'] = (string) $this->companyId;
        }

        if ($stage !== null && $stage !== '') {
            $params['stage'] = (string) $stage;
        }

        return $params;
    }

    /**
     * Query params for the orders listing page (preserves dashboard filters).
     *
     * @return array<string, string>
     */
    public function toOrdersListingQueryArray(?string $status = null): array
    {
        $params = $this->toQueryArray();

        if ($this->companyId) {
            unset($params['company_id']);
            $params['company'] = (string) $this->companyId;
        }

        if ($status !== null && $status !== '') {
            $params['status'] = (string) $status;
        }

        return $params;
    }

    public function isCustomPeriod(): bool
    {
        return $this->period === self::PERIOD_CUSTOM;
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            self::PERIOD_THIS_MONTH => 'This month',
            self::PERIOD_CUSTOM => $this->customRangeLabel(),
            default => 'All time',
        };
    }

    public function customRangeLabel(): string
    {
        if ($this->dateFrom && $this->dateTo) {
            return Carbon::parse($this->dateFrom)->format('d M Y')
                .' – '
                .Carbon::parse($this->dateTo)->format('d M Y');
        }

        if ($this->dateFrom) {
            return 'From '.Carbon::parse($this->dateFrom)->format('d M Y');
        }

        if ($this->dateTo) {
            return 'Until '.Carbon::parse($this->dateTo)->format('d M Y');
        }

        return 'Date to date';
    }

    public function shouldIncludeStageSlug(?string $slug): bool
    {
        return true;
    }

    public function applyDateScope(Builder|Relation $query, string $column = 'created_at'): void
    {
        match ($this->period) {
            self::PERIOD_THIS_MONTH => $query->where($column, '>=', now()->startOfMonth()),
            self::PERIOD_CUSTOM => $this->applyCustomDateBounds($query, $column),
            default => null,
        };
    }

    public function applyReportDateScope(Builder|Relation $query): void
    {
        if ($this->period === self::PERIOD_ALL) {
            return;
        }

        if ($this->period === self::PERIOD_THIS_MONTH) {
            $query->where(function (Builder $q) {
                $q->where('delivered_at', '>=', now()->startOfMonth())
                    ->orWhere(function (Builder $inner) {
                        $inner->whereNull('delivered_at')
                            ->where('created_at', '>=', now()->startOfMonth());
                    });
            });

            return;
        }

        if ($this->period !== self::PERIOD_CUSTOM) {
            return;
        }

        $from = $this->dateFrom;
        $to = $this->dateTo;

        if ($from === null && $to === null) {
            return;
        }

        $query->where(function (Builder $q) use ($from, $to) {
            $q->where(function (Builder $delivered) use ($from, $to) {
                $delivered->whereNotNull('delivered_at');
                if ($from !== null) {
                    $delivered->whereDate('delivered_at', '>=', $from);
                }
                if ($to !== null) {
                    $delivered->whereDate('delivered_at', '<=', $to);
                }
            })->orWhere(function (Builder $pending) use ($from, $to) {
                $pending->whereNull('delivered_at');
                if ($from !== null) {
                    $pending->whereDate('created_at', '>=', $from);
                }
                if ($to !== null) {
                    $pending->whereDate('created_at', '<=', $to);
                }
            });
        });
    }

    public function applyCaseScope(Builder|Relation $query): void
    {
        $this->applyDateScope($query);
        $this->applyCompanyScope($query);
        $this->applyTeamUserCaseScope($query);
    }

    public function applyOrderScope(Builder|Relation $query, string $dateColumn = 'created_at'): void
    {
        $this->applyDateScope($query, $dateColumn);
        $this->applyCompanyScope($query);

        if ($this->teamUserId) {
            $teamUserId = $this->teamUserId;
            $query->whereHas('caseFile', fn (Builder $case) => $case->forAnalyst($teamUserId));
        }
    }

    public function applyCompanyScope(Builder|Relation $query, string $column = 'company_id'): void
    {
        if (! $this->companyId) {
            return;
        }

        $ids = CompanyFilter::equivalentCompanyIds($this->companyId);

        if (count($ids) === 1) {
            $query->where($column, $ids[0]);
        } else {
            $query->whereIn($column, $ids);
        }
    }

    public function applyTeamUserCaseScope(Builder|Relation $query): void
    {
        if ($this->teamUserId) {
            $query->forAnalyst($this->teamUserId);
        }
    }

    public function teamUserLabel(): ?string
    {
        if (! $this->teamUserId) {
            return null;
        }

        return User::employees()
            ->whereKey($this->teamUserId)
            ->first()
            ?->displayNameWithRole();
    }

    public function companyLabel(): ?string
    {
        if (! $this->companyId) {
            return null;
        }

        return Company::query()->whereKey($this->companyId)->value('name');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function teamMemberOptions(): array
    {
        return User::employees()
            ->where('is_active', true)
            ->orderBy('role')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => $user->displayNameWithRole(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function companyOptions(): array
    {
        return collect(CompanyFilter::options())
            ->map(fn (string $name, int $id) => [
                'value' => (string) $id,
                'label' => $name,
            ])
            ->values()
            ->all();
    }

    public function applyAssignedCaseScope(Builder|Relation $query): void
    {
        // All stages visible; no date filter unless period is set.
    }

    public function applyAssignedCaseScopeWithPeriod(Builder|Relation $query): void
    {
        $this->applyDateScope($query);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function periodOptions(): array
    {
        return [
            ['value' => self::PERIOD_ALL, 'label' => 'All time'],
            ['value' => self::PERIOD_THIS_MONTH, 'label' => 'This month'],
            ['value' => self::PERIOD_CUSTOM, 'label' => 'Date to date'],
        ];
    }

    private function applyCustomDateBounds(Builder|Relation $query, string $column): void
    {
        if ($this->dateFrom !== null) {
            $query->whereDate($column, '>=', $this->dateFrom);
        }
        if ($this->dateTo !== null) {
            $query->whereDate($column, '<=', $this->dateTo);
        }
    }

    private static function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function parseTeamUserId(mixed $value): ?int
    {
        $id = (int) $value;

        if ($id < 1) {
            return null;
        }

        return User::employees()->whereKey($id)->exists() ? $id : null;
    }

    private static function parseCompanyId(mixed $value): ?int
    {
        $id = (int) $value;

        if ($id < 1) {
            return null;
        }

        return Company::query()->whereKey($id)->exists() ? $id : null;
    }
}
