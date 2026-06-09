<?php

namespace App\Support;

use App\Models\ServicePackage;
use App\Models\User;
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

    /** @return array<int, string> */
    public static function packageOptions(): array
    {
        return ServicePackage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    public static function apply(Builder $query, Request $request, bool $clientStages = false): void
    {
        if ($search = trim((string) $request->input('q'))) {
            self::applySearch($query, $search);
        }

        CompanyFilter::apply($query, $request);

        if ($clientStages) {
            self::applyClientStage($query, $request->input('stage'));
        } else {
            self::applyStage($query, $request->input('stage'));
        }

        self::applyPackage($query, $request->input('package'));

        $teamUserId = (int) $request->input('team_user');
        if ($teamUserId > 0 && User::employees()->whereKey($teamUserId)->exists()) {
            $query->forAnalyst($teamUserId);
        }

        DashboardFilters::fromRequestQuery($request)->applyDateScope($query, 'created_at');
        CaseListSorting::apply($query, $request);
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return $request->filled('q')
            || $request->filled('company')
            || $request->filled('stage')
            || $request->filled('package')
            || $request->filled('team_user')
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

        $query->whereHas('order', fn (Builder $order) => $order->where('service_package_id', $packageId));
    }

    public static function applyClientStage(Builder $query, mixed $clientStage): void
    {
        if ($clientStage === null || $clientStage === '') {
            return;
        }

        $clientStageSlug = (string) $clientStage;
        $internalSlugs = CaseWorkflow::internalSlugsForClientStage($clientStageSlug);

        if ($internalSlugs === []) {
            return;
        }

        $stageIds = WorkflowStage::query()
            ->whereIn('slug', $internalSlugs)
            ->pluck('id');

        $query->where(function (Builder $q) use ($stageIds, $clientStageSlug) {
            $q->whereIn('workflow_stage_id', $stageIds);

            if ($clientStageSlug === CaseWorkflow::CLIENT_STAGE_ORDER_CONFIRMED) {
                $q->orWhereNull('workflow_stage_id');
            }
        });
    }

    public static function applyStage(Builder $query, mixed $stage): void
    {
        if ($stage === null || $stage === '') {
            return;
        }

        $stageId = (int) $stage;
        $assignedStageId = WorkflowStage::query()
            ->where('slug', CaseWorkflow::SLUG_ASSIGNED)
            ->value('id');

        if ($assignedStageId && $stageId === (int) $assignedStageId) {
            $query->where(function (Builder $q) use ($stageId) {
                $q->where('workflow_stage_id', $stageId)
                    ->orWhereNull('workflow_stage_id');
            });

            return;
        }

        $query->where('workflow_stage_id', $stageId);
    }

    public static function applySearch(Builder $query, string $search): void
    {
        $like = '%'.$search.'%';

        $query->where(function (Builder $q) use ($like) {
            $q->where('reference', 'like', $like)
                ->orWhereHas('order', function (Builder $order) use ($like) {
                    $order->where('reference', 'like', $like)
                        ->orWhere('subject_name', 'like', $like)
                        ->orWhere('subject_details', 'like', $like)
                        ->orWhere('custom_request', 'like', $like);
                })
                ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', $like));
        });
    }

}
