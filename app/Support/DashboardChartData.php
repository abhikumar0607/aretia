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
    public static function forAdmin(): array
    {
        return [
            self::buildChart('admin', 'onboarding', 'Clients by onboarding status', self::companyStatusSlices()),
            self::buildChart('admin', 'orders', 'Orders by status', self::orderStatusSlices()),
            self::buildChart('admin', 'cases-stage', 'Cases by workflow stage', self::casesByStage()),
        ];
    }

    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forSuperAdmin(): array
    {
        return [
            ...self::forAdmin(),
            self::buildChart('superadmin', 'users-role', 'Platform users by role', self::usersByRoleSlices()),
        ];
    }

    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forClient(?int $companyId): array
    {
        if (! $companyId) {
            return [
                self::buildChart('client', 'orders', 'Orders by status', []),
                self::buildChart('client', 'cases-stage', 'Cases by workflow stage', []),
                self::buildChart('client', 'reports', 'Reports', []),
            ];
        }

        return [
            self::buildChart('client', 'orders', 'Orders by status', self::orderStatusSlices(
                fn (Builder $q) => $q->where('company_id', $companyId)
            )),
            self::buildChart('client', 'cases-stage', 'Cases by workflow stage', self::casesByStage(
                fn (Builder $q) => $q->where('company_id', $companyId)
            )),
            self::buildChart('client', 'reports', 'Reports', self::reportSlices(
                fn (Builder $q) => $q->whereHas('caseFile', fn (Builder $c) => $c->where('company_id', $companyId))
            )),
        ];
    }

    /**
     * @return list<array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}>
     */
    public static function forAnalyst(int $userId): array
    {
        $caseScope = fn (Builder $q) => $q->forAnalyst($userId);

        $byStatus = [];
        foreach (CaseFile::query()->forAnalyst($userId)->selectRaw('status, count(*) as total')->groupBy('status')->get() as $row) {
            if ((int) $row->total > 0) {
                $byStatus[] = [
                    'label' => ucfirst((string) $row->status),
                    'value' => (int) $row->total,
                    'color' => $row->status === 'open' ? '#4f46e5' : '#64748b',
                ];
            }
        }

        return [
            self::buildChart('analyst', 'cases-stage', 'Cases by workflow stage', self::casesByStage($caseScope)),
            self::buildChart('analyst', 'cases-status', 'Cases by status', $byStatus),
            self::buildChart('analyst', 'reports', 'Reports on my cases', self::reportSlices(
                fn (Builder $q) => $q->whereHas('caseFile', fn (Builder $c) => $c->forAnalyst($userId))
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
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function orderStatusSlices(?callable $scope = null): array
    {
        $rows = [];
        foreach (OrderStatus::cases() as $status) {
            $query = Order::where('status', $status);
            if ($scope) {
                $scope($query);
            }
            $count = $query->count();
            if ($count > 0) {
                $rows[] = [
                    'label' => self::orderStatusLabel($status),
                    'value' => $count,
                    'color' => self::orderStatusColor($status),
                ];
            }
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
                    'label' => $role->label(),
                    'value' => $count,
                    'color' => $colors[$role->value] ?? '#94a3b8',
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  callable(Builder): void  $scope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function reportSlices(callable $scope): array
    {
        $base = Report::query();
        $scope($base);

        $delivered = (clone $base)->whereNotNull('delivered_at')->count();
        $pending = (clone $base)->whereNull('delivered_at')->count();

        $rows = [];
        if ($delivered > 0) {
            $rows[] = ['label' => 'Delivered', 'value' => $delivered, 'color' => '#059669'];
        }
        if ($pending > 0) {
            $rows[] = ['label' => 'In progress', 'value' => $pending, 'color' => '#f59e0b'];
        }

        return $rows;
    }

    /**
     * @param  callable(Builder): void|null  $scope
     * @return list<array{label: string, value: int, color: string}>
     */
    private static function casesByStage(?callable $scope = null): array
    {
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();
        $rows = [];

        foreach ($stages as $stage) {
            $query = CaseFile::where('workflow_stage_id', $stage->id);
            if ($scope) {
                $scope($query);
            }
            $count = $query->count();
            if ($count > 0) {
                $rows[] = [
                    'label' => $stage->name,
                    'value' => $count,
                    'color' => $stage->color ?: '#94a3b8',
                ];
            }
        }

        $unassigned = CaseFile::whereNull('workflow_stage_id');
        if ($scope) {
            $scope($unassigned);
        }
        $unassignedCount = $unassigned->count();
        if ($unassignedCount > 0) {
            $rows[] = [
                'label' => 'Unassigned',
                'value' => $unassignedCount,
                'color' => '#cbd5e1',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{label: string, value: int, color: string}>  $slices
     * @return array{id: string, title: string, labels: list<string>, values: list<int>, colors: list<string>}
     */
    private static function buildChart(string $prefix, string $key, string $title, array $slices): array
    {
        return [
            'id' => $prefix.'-chart-'.$key,
            'title' => $title,
            'labels' => array_column($slices, 'label'),
            'values' => array_column($slices, 'value'),
            'colors' => array_column($slices, 'color'),
        ];
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
        return match ($status) {
            OrderStatus::Draft => 'Draft',
            OrderStatus::Pending => 'Pending',
            OrderStatus::Confirmed => 'Confirmed',
            OrderStatus::Cancelled => 'Cancelled',
        };
    }

    private static function orderStatusColor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Draft => '#94a3b8',
            OrderStatus::Pending => '#f59e0b',
            OrderStatus::Confirmed => '#059669',
            OrderStatus::Cancelled => '#dc2626',
        };
    }
}
