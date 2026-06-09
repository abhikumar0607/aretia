<?php

namespace App\Support;

use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Document;
use App\Models\Order;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuditLogFilters
{
    public static function apply(Builder $query, Request $request): void
    {
        self::applyCompany($query, $request);
        self::applyCaseSearch($query, $request);
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('company') || $request->filled('q');
    }

    private static function applyCompany(Builder $query, Request $request): void
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

    private static function applyCaseSearch(Builder $query, Request $request): void
    {
        $term = trim((string) $request->input('q', ''));
        if ($term === '') {
            return;
        }

        $cases = self::resolveCases($term);

        $query->where(function (Builder $inner) use ($cases, $term) {
            if ($cases->isNotEmpty()) {
                $caseIds = $cases->pluck('id')->all();
                $references = $cases->pluck('reference')->all();
                $orderIds = $cases->pluck('order_id')->filter()->values()->all();

                $inner
                    ->where(function (Builder $q) use ($caseIds) {
                        $q->where('auditable_type', CaseFile::class)
                            ->whereIn('auditable_id', $caseIds);
                    });

                if ($orderIds !== []) {
                    $inner->orWhere(function (Builder $q) use ($orderIds) {
                        $q->where('auditable_type', Order::class)
                            ->whereIn('auditable_id', $orderIds);
                    });
                }

                $inner
                    ->orWhereHasMorph('auditable', [Report::class], fn (Builder $q) => $q->whereIn('case_id', $caseIds))
                    ->orWhereHasMorph('auditable', [Document::class], function (Builder $q) use ($caseIds) {
                        $q->where('documentable_type', CaseFile::class)
                            ->whereIn('documentable_id', $caseIds);
                    });

                foreach ($references as $reference) {
                    $inner->orWhere('properties->case_reference', $reference);
                }

                foreach ($caseIds as $caseId) {
                    $inner->orWhere('properties->case_id', $caseId)
                        ->orWhereJsonContains('properties->case_ids', $caseId);
                }
            }

            $like = '%'.$term.'%';
            $inner->orWhere('properties->case_reference', 'like', $like);

            if (ctype_digit($term)) {
                $inner->orWhere('properties->case_id', (int) $term);
            }
        });
    }

    /**
     * @return Collection<int, CaseFile>
     */
    private static function resolveCases(string $term): Collection
    {
        $query = CaseFile::query();

        if (ctype_digit($term)) {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('id', (int) $term)
                    ->orWhere('reference', 'like', '%'.$term.'%');
            });
        } else {
            $query->where('reference', 'like', '%'.$term.'%');
        }

        return $query->get();
    }
}
