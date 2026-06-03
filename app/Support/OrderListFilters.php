<?php

namespace App\Support;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderListFilters
{
    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])
            ->all();
    }

    public static function apply(Builder $query, Request $request): void
    {
        CompanyFilter::apply($query, $request);

        $status = (string) $request->input('status');
        if ($status !== '' && in_array($status, array_column(OrderStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        DashboardFilters::fromRequestQuery($request)->applyDateScope($query, 'created_at');
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('q')
            || $request->filled('company')
            || $request->filled('status')
            || ! DashboardFilters::fromRequestQuery($request)->isDefault();
    }
}
