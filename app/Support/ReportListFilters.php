<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportListFilters
{
    public static function apply(Builder $query, Request $request): void
    {
        if ($search = trim((string) $request->input('q'))) {
            self::applySearch($query, $search);
        }

        if ($request->filled('company')) {
            $ids = CompanyFilter::equivalentCompanyIds((int) $request->input('company'));
            $query->whereHas('caseFile', fn (Builder $case) => $case->whereIn('company_id', $ids));
        }

        DashboardFilters::fromRequestQuery($request)->applyReportDateScope($query);
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('q')
            || $request->filled('company')
            || ! DashboardFilters::fromRequestQuery($request)->isDefault();
    }

    public static function applySearch(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('original_name', 'like', $like)
                ->orWhereHas('caseFile', fn (Builder $case) => $case
                    ->where('reference', 'like', $like)
                    ->orWhereHas('order', fn (Builder $order) => $order
                        ->where('subject_name', 'like', $like)
                        ->orWhere('reference', 'like', $like)))
                ->orWhereHas('caseFile.company', fn (Builder $company) => $company->where('name', 'like', $like))
                ->orWhereHas('uploader', fn (Builder $user) => $user->where('name', 'like', $like));
        });
    }
}
