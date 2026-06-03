<?php

namespace App\Support;

use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CaseListFilters
{
    /** @return array<int, string> */
    public static function stageOptions(): array
    {
        return WorkflowStage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    public static function apply(Builder $query, Request $request): void
    {
        if ($search = trim((string) $request->input('q'))) {
            self::applySearch($query, $search);
        }

        CompanyFilter::apply($query, $request);

        $stage = $request->input('stage');
        if ($stage !== null && $stage !== '') {
            $query->where('workflow_stage_id', (int) $stage);
        }

        DashboardFilters::fromRequestQuery($request)->applyDateScope($query, 'created_at');
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('q')
            || $request->filled('company')
            || $request->filled('stage')
            || ! DashboardFilters::fromRequestQuery($request)->isDefault();
    }

    public static function applySearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('reference', 'like', "%{$search}%")
                ->orWhereHas('order', function ($order) use ($search) {
                    $order->where('subject_name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%");
                })
                ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%"));
        });
    }

}
