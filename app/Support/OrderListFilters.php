<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\ServicePackage;
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

    /** @return array<int, string> */
    public static function packageOptions(): array
    {
        return CaseListFilters::packageOptions();
    }

    public static function apply(Builder $query, Request $request): void
    {
        if ($search = trim((string) $request->input('q'))) {
            self::applySearch($query, $search);
        }

        CompanyFilter::apply($query, $request);

        $status = (string) $request->input('status');
        if ($status !== '' && in_array($status, array_column(OrderStatus::cases(), 'value'), true)) {
            $query->where('status', $status);
        }

        self::applyPackage($query, $request->input('package'));

        DashboardFilters::fromRequestQuery($request)->applyDateScope($query, 'created_at');
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('q')
            || $request->filled('company')
            || $request->filled('status')
            || $request->filled('package')
            || ! DashboardFilters::fromRequestQuery($request)->isDefault();
    }

    public static function applyPackage(Builder $query, mixed $package): void
    {
        $packageId = (int) $package;

        if ($packageId < 1) {
            return;
        }

        if (! ServicePackage::query()->whereKey($packageId)->where('is_active', true)->exists()) {
            return;
        }

        $query->where('service_package_id', $packageId);
    }

    public static function applySearch(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('reference', 'like', $like)
                ->orWhere('subject_name', 'like', $like)
                ->orWhere('subject_details', 'like', $like)
                ->orWhere('custom_request', 'like', $like)
                ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }
}
