<?php

namespace App\Support;

use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Document;
use App\Models\Order;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogFilters
{
    public static function apply(Builder $query, Request $request): void
    {
        $company = $request->input('company');
        if ($company === null || $company === '') {
            return;
        }

        $companyIds = CompanyFilter::equivalentCompanyIds((int) $company);

        $query->where(function (Builder $inner) use ($companyIds) {
            $inner
                ->whereHasMorph('auditable', [Order::class], fn (Builder $q) => $q->whereIn('company_id', $companyIds))
                ->orWhereHasMorph('auditable', [CaseFile::class], fn (Builder $q) => $q->whereIn('company_id', $companyIds))
                ->orWhereHasMorph('auditable', [Company::class], fn (Builder $q) => $q->whereIn('id', $companyIds))
                ->orWhereHasMorph('auditable', [Report::class], function (Builder $q) use ($companyIds) {
                    $q->whereHas('caseFile', fn (Builder $c) => $c->whereIn('company_id', $companyIds));
                })
                ->orWhereHasMorph('auditable', [Document::class], function (Builder $q) use ($companyIds) {
                    $q->where('documentable_type', CaseFile::class)
                        ->whereHasMorph('documentable', [CaseFile::class], fn (Builder $c) => $c->whereIn('company_id', $companyIds));
                });
        });
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('company') || $request->filled('q');
    }
}
