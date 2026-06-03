<?php

namespace App\Support;

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
    ) {}

    public static function fromRequest(Request $request, ?string $namespace = null): self
    {
        $namespace ??= auth()->check() ? auth()->user()->role->value : 'guest';
        $sessionKey = 'dashboard_filters.'.$namespace;

        $hasFilterInput = $request->has('period')
            || $request->has('date_from')
            || $request->has('date_to');

        if (! $hasFilterInput && $request->session()->has($sessionKey)) {
            return self::fromSessionArray((array) $request->session()->get($sessionKey, []));
        }

        $filters = self::fromRequestQuery($request);

        $request->session()->put($sessionKey, [
            'period' => $filters->period,
            'date_from' => $filters->dateFrom,
            'date_to' => $filters->dateTo,
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

            return new self(period: $period, dateFrom: $dateFrom, dateTo: $dateTo);
        }

        if ($request->filled('due_from') || $request->filled('due_to')) {
            return new self(
                period: self::PERIOD_CUSTOM,
                dateFrom: self::parseDate($request->input('due_from')),
                dateTo: self::parseDate($request->input('due_to')),
            );
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            return new self(
                period: self::PERIOD_CUSTOM,
                dateFrom: self::parseDate($request->input('date_from')),
                dateTo: self::parseDate($request->input('date_to')),
            );
        }

        return new self();
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

        return $params;
    }

    public function isDefault(): bool
    {
        return $this->period === self::PERIOD_ALL;
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
}
