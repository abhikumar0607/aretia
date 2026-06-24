<?php

namespace App\Support;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Builder;

class DashboardChartData
{
    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forAdmin(?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;

        return [
            self::buildChart('admin', 'cases-stage', 'Cases by workflow stage', self::platformCasesByStage($filters), true),
            self::buildChart('admin', 'orders', 'Orders by status', self::orderStatusSlices(
                null,
                fn (Builder $q) => $filters->applyOrderScope($q),
                true
            ), true),
        ];
    }

    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forSuperAdmin(?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;

        return [
            self::buildChart('superadmin', 'cases-stage', 'Cases by workflow stage', self::platformCasesByStage($filters), true),
            self::buildChart('superadmin', 'orders', 'Orders by status', self::orderStatusSlices(
                null,
                fn (Builder $q) => $filters->applyOrderScope($q),
                true
            ), true),
        ];
    }

    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forClient(?int $companyId, ?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;

        if (! $companyId) {
            return [
                self::buildChart('client', 'cases-stage', 'Cases by workflow stage', []),
                self::buildChart('client', 'orders', 'Orders by status', []),
                self::buildChart('client', 'reports', 'Reports on your cases', []),
            ];
        }

        $companyScope = fn (Builder $q) => $q->where('company_id', $companyId);

        return [
            self::buildChart('client', 'cases-stage', 'Your cases by stage', self::clientCompanyCasesByStage($companyId, $filters), true),
            self::buildChart('client', 'orders', 'Orders by status', self::orderStatusSlices(
                $companyScope,
                fn (Builder $q) => $filters->applyDateScope($q),
                true
            ), true),
            self::buildChart('client', 'reports', 'Reports on your cases', self::reportSlices(
                fn (Builder $q) => $q->where('company_id', $companyId),
                $filters,
            ), true),
        ];
    }

    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forAnalyst(int $userId, ?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;
        $caseScope = fn (Builder $q) => $q->forAnalyst($userId);

        return [
            self::buildChart('analyst', 'cases-stage', 'My cases by stage', self::casesByStage($caseScope, $filters, false, true)),
            self::buildChart('analyst', 'reports', 'Reports on assigned cases', self::reportSlices(
                fn (Builder $q) => $q->forAnalyst($userId),
                $filters,
                true,
            )),
        ];
    }

    /**
     * @param  callable(Builder): void|null  $scope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function companyStatusSlices(?callable $scope = null): array
    {
        $rows = [];
        foreach (CompanyStatus::cases() as $status) {
            $query = Company::where('status', $status);
            if ($scope) {
                $scope($query);
            }
            $count = $query->count();
            if ($count > 0) {
                $rows[] = [
                    'label' => self::companyStatusLabel($status),
                    'value' => $count,
                    'color' => self::companyStatusColor($status),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  callable(Builder): void|null  $scope
     * @param  callable(Builder): void|null  $dateScope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function orderStatusSlices(?callable $scope = null, ?callable $dateScope = null, bool $includeAllStatuses = false): array
    {
        $rows = [];
        foreach (OrderStatus::cases() as $status) {
            $query = Order::where('status', $status);
            if ($scope) {
                $scope($query);
            }
            if ($dateScope) {
                $dateScope($query);
            }
            $count = $query->count();
            if ($count > 0 || $includeAllStatuses) {
                $rows[] = [
                    'label' => self::orderStatusLabel($status),
                    'value' => $count,
                    'color' => self::orderStatusColor($status),
                    'order_status' => $status->value,
                ];
            }
        }

        return $rows;
    }

    /**
     * Platform-wide case counts per workflow stage (all cases, not limited by created_at).
     *
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function platformCasesByStage(?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();
        $rows = [];

        foreach ($stages as $stage) {
            if (! $filters->shouldIncludeStageSlug($stage->slug)) {
                continue;
            }

            $query = CaseFile::query();
            $filters->applyCaseScope($query);
            $query->where(function (Builder $q) use ($stage) {
                $q->where('workflow_stage_id', $stage->id);
                if ($stage->slug === CaseWorkflow::SLUG_ASSIGNED) {
                    $q->orWhereNull('workflow_stage_id');
                }
            });

            $rows[] = [
                'label' => $stage->name,
                'value' => $query->count(),
                'color' => $stage->color ?: '#94a3b8',
                'stage_id' => $stage->id,
            ];
        }

        return $rows;
    }

    /**
     * Company cases per workflow stage.
     *
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function companyCasesByStage(int $companyId, ?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();
        $rows = [];

        foreach ($stages as $stage) {
            if (! $filters->shouldIncludeStageSlug($stage->slug)) {
                continue;
            }

            $query = CaseFile::where('company_id', $companyId);
            $filters->applyCaseScope($query);
            $query->where(function (Builder $q) use ($stage) {
                $q->where('workflow_stage_id', $stage->id);
                if ($stage->slug === CaseWorkflow::SLUG_ASSIGNED) {
                    $q->orWhereNull('workflow_stage_id');
                }
            });

            $rows[] = [
                'label' => $stage->name,
                'value' => $query->count(),
                'color' => $stage->color ?: '#94a3b8',
                'stage_id' => $stage->id,
            ];
        }

        return $rows;
    }

    /**
     * Client-facing case stages for dashboard (simplified pipeline).
     *
     * @return list<array{label: string, value: int, color: string, client_stage_slug: string}>
     */
    private static function clientCompanyCasesByStage(int $companyId, ?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;
        $rows = [];

        foreach (CaseWorkflow::clientStageOptions() as $clientSlug => $label) {
            $query = CaseFile::where('company_id', $companyId);
            $filters->applyCaseScope($query);
            CaseListFilters::applyClientStage($query, $clientSlug);

            $rows[] = [
                'label' => $label,
                'value' => $query->count(),
                'color' => CaseWorkflow::clientStageColor(
                    CaseWorkflow::internalSlugsForClientStage($clientSlug)[0] ?? CaseWorkflow::SLUG_ASSIGNED
                ),
                'client_stage_slug' => $clientSlug,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function usersByRoleSlices(): array
    {
        $colors = [
            UserRole::SuperAdmin->value => '#7c3aed',
            UserRole::Admin->value => '#4f46e5',
            UserRole::Client->value => '#059669',
            UserRole::Analyst->value => '#2563eb',
        ];

        $rows = [];
        foreach (UserRole::cases() as $role) {
            $count = User::where('role', $role)->count();
            if ($count > 0) {
                $rows[] = [
                    'label' => $role->chartLabel(),
                    'value' => $count,
                    'color' => $colors[$role->value] ?? '#94a3b8',
                ];
            }
        }

        return $rows;
    }

    /**
     * Cases with a delivered report vs cases still awaiting their first delivery.
     *
     * @param  callable(Builder): void  $caseScope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function reportSlices(
        callable $caseScope,
        ?DashboardFilters $filters = null,
        bool $assignedWorkload = false,
        bool $includeAllStatuses = false,
    ): array {
        $cases = CaseFile::query();
        $caseScope($cases);

        if ($filters) {
            if ($assignedWorkload) {
                $filters->applyAssignedCaseScopeWithPeriod($cases);
            } else {
                $filters->applyCaseScope($cases);
            }
        }

        $delivered = (clone $cases)
            ->whereHas('latestReport', fn (Builder $q) => $q->whereNotNull('delivered_at'))
            ->count();
        $inProgress = (clone $cases)
            ->whereDoesntHave('latestReport', fn (Builder $q) => $q->whereNotNull('delivered_at'))
            ->count();

        $rows = [];
        if ($delivered > 0 || $includeAllStatuses) {
            $rows[] = ['label' => 'Delivered', 'value' => $delivered, 'color' => '#059669'];
        }
        if ($inProgress > 0 || $includeAllStatuses) {
            $rows[] = ['label' => 'In progress', 'value' => $inProgress, 'color' => '#f59e0b'];
        }

        return $rows;
    }

    /**
     * @param  callable(Builder)|null  $scope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function casesByStage(
        ?callable $scope = null,
        ?DashboardFilters $filters = null,
        bool $includeEmptyStages = false,
        bool $assignedWorkload = false,
    ): array {
        $filters ??= new DashboardFilters;
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();
        $rows = [];

        $applyCaseFilters = function (Builder $query) use ($filters, $assignedWorkload): void {
            if ($assignedWorkload) {
                $filters->applyAssignedCaseScopeWithPeriod($query);
            } else {
                $filters->applyCaseScope($query);
            }
        };

        $assignedStageId = WorkflowStage::query()
            ->where('slug', CaseWorkflow::SLUG_ASSIGNED)
            ->value('id');

        foreach ($stages as $stage) {
            if (! $filters->shouldIncludeStageSlug($stage->slug)) {
                continue;
            }

            $query = CaseFile::where('workflow_stage_id', $stage->id);
            if ($scope) {
                $scope($query);
            }
            $applyCaseFilters($query);
            $count = $query->count();
            if ($count > 0 || $includeEmptyStages) {
                $rows[] = [
                    'label' => $stage->name,
                    'value' => $count,
                    'color' => $stage->color ?: '#94a3b8',
                    'stage_id' => $stage->id,
                ];
            }
        }

        $unassigned = CaseFile::whereNull('workflow_stage_id');
        if ($scope) {
            $scope($unassigned);
        }
        $applyCaseFilters($unassigned);
        $unassignedCount = $unassigned->count();
        if ($unassignedCount > 0 || ($includeEmptyStages && $filters->shouldIncludeStageSlug(null))) {
            $rows[] = [
                'label' => 'Unassigned stage',
                'value' => $unassignedCount,
                'color' => '#cbd5e1',
                'stage_id' => $assignedStageId ? (int) $assignedStageId : null,
            ];
        }

        return $rows;
    }

    /**
     * @param  callable(Builder): void  $scope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function caseStatusSlices(callable $scope, ?DashboardFilters $filters = null): array
    {
        $filters ??= new DashboardFilters;
        $query = CaseFile::query();
        $scope($query);
        $filters->applyCaseScope($query);

        $rows = [];
        foreach ($query->selectRaw('status, count(*) as total')->groupBy('status')->orderBy('status')->get() as $row) {
            $count = (int) $row->total;
            if ($count < 1) {
                continue;
            }
            $status = (string) $row->status;
            $rows[] = [
                'label' => ucfirst($status),
                'value' => $count,
                'color' => $status === 'open' ? '#4f46e5' : '#64748b',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{label: string, value: int, color: string}>  $slices
     * @return array{
     *     id: string,
     *     key: string,
     *     title: string,
     *     subtitle: string,
     *     type: string,
     *     variant: string,
     *     layout: string,
     *     horizontal?: bool,
     *     labels: list<string>,
     *     values: list<int>,
     *     colors: list<string>
     * }
     */
    private static function buildChart(string $prefix, string $key, string $title, array $slices, bool $showAllSlices = false): array
    {
        $meta = self::chartMeta($key);
        $nonZero = array_values(array_filter($slices, fn (array $slice): bool => ($slice['value'] ?? 0) > 0));

        return [
            'id' => $prefix.'-chart-'.$key,
            'key' => $key,
            'title' => $title,
            'subtitle' => $meta['subtitle'],
            'type' => $meta['type'],
            'variant' => $meta['variant'],
            'layout' => $meta['layout'],
            'horizontal' => $meta['horizontal'] ?? false,
            'show_all_slices' => $showAllSlices,
            'labels' => array_column($slices, 'label'),
            'values' => array_column($slices, 'value'),
            'colors' => array_column($slices, 'color'),
            'canvas_labels' => array_column($nonZero, 'label'),
            'canvas_values' => array_column($nonZero, 'value'),
            'canvas_colors' => array_column($nonZero, 'color'),
            'stage_ids' => array_map(
                fn (array $slice) => isset($slice['stage_id']) ? (int) $slice['stage_id'] : null,
                $slices
            ),
            'client_stage_slugs' => array_map(
                fn (array $slice) => $slice['client_stage_slug'] ?? null,
                $slices
            ),
            'canvas_stage_ids' => array_values(array_map(
                fn (array $slice) => isset($slice['stage_id']) ? (int) $slice['stage_id'] : null,
                $nonZero
            )),
            'canvas_client_stage_slugs' => array_values(array_map(
                fn (array $slice) => $slice['client_stage_slug'] ?? null,
                $nonZero
            )),
            'order_statuses' => array_map(
                fn (array $slice) => $slice['order_status'] ?? null,
                $slices
            ),
            'canvas_order_statuses' => array_values(array_map(
                fn (array $slice) => $slice['order_status'] ?? null,
                $nonZero
            )),
        ];
    }

    /**
     * @return array{type: string, variant: string, subtitle: string, layout: string, horizontal?: bool}
     */
    private static function chartMeta(string $key): array
    {
        return match ($key) {
            'onboarding' => [
                'type' => 'doughnut',
                'variant' => 'onboarding',
                'subtitle' => 'Onboarding pipeline',
                'layout' => 'ring',
            ],
            'orders' => [
                'type' => 'bar',
                'variant' => 'orders',
                'subtitle' => 'Pending, confirmed, and rejected',
                'layout' => 'bars-h',
                'horizontal' => true,
            ],
            'cases-stage' => [
                'type' => 'pie',
                'variant' => 'stages',
                'subtitle' => 'All cases across pipeline stages',
                'layout' => 'ring',
            ],
            'reports' => [
                'type' => 'bar',
                'variant' => 'reports',
                'subtitle' => 'Cases with report delivered vs awaiting report',
                'layout' => 'bars',
                'horizontal' => false,
            ],
            'cases-status' => [
                'type' => 'doughnut',
                'variant' => 'status',
                'subtitle' => 'Open vs other statuses',
                'layout' => 'ring',
            ],
            'users-role' => [
                'type' => 'pie',
                'variant' => 'users',
                'subtitle' => 'Platform team breakdown',
                'layout' => 'ring',
            ],
            default => [
                'type' => 'doughnut',
                'variant' => 'default',
                'subtitle' => 'Distribution overview',
                'layout' => 'ring',
            ],
        };
    }

    private static function companyStatusLabel(CompanyStatus $status): string
    {
        return match ($status) {
            CompanyStatus::Pending => 'Pending',
            CompanyStatus::KycSubmitted => 'KYC submitted',
            CompanyStatus::Active => 'Active',
            CompanyStatus::Rejected => 'Rejected',
        };
    }

    private static function companyStatusColor(CompanyStatus $status): string
    {
        return match ($status) {
            CompanyStatus::Pending => '#f59e0b',
            CompanyStatus::KycSubmitted => '#3b82f6',
            CompanyStatus::Active => '#059669',
            CompanyStatus::Rejected => '#dc2626',
        };
    }

    private static function orderStatusLabel(OrderStatus $status): string
    {
        return $status->label();
    }

    private static function orderStatusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => '#f59e0b',
            OrderStatus::Confirmed => '#059669',
            OrderStatus::Rejected => '#dc2626',
        };
    }
}
