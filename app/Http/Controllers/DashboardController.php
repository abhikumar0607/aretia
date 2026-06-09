<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardChartData;
use App\Support\DashboardFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function superadmin(Request $request): View
    {
        $dashboardFilters = DashboardFilters::fromRequest($request, 'superadmin');
        $stats = $this->adminStats($dashboardFilters);
        $charts = DashboardChartData::forSuperAdmin($dashboardFilters);

        return view('superadmin.dashboard', compact('stats', 'charts', 'dashboardFilters'));
    }

    public function admin(Request $request): View
    {
        return $this->adminDashboard($request, 'admin.dashboard');
    }

    private function adminDashboard(Request $request, string $view): View
    {
        $dashboardFilters = DashboardFilters::fromRequest($request, 'admin');
        $stats = $this->adminStats($dashboardFilters);
        $charts = DashboardChartData::forAdmin($dashboardFilters);

        return view($view, compact('stats', 'charts', 'dashboardFilters'));
    }

    /**
     * @return array{client_companies: int, active_clients: int, employees: int, pending_onboarding: int, orders: int, active_cases: int, reports_ready: int}
     */
    private function adminStats(DashboardFilters $filters): array
    {
        $ordersQuery = Order::query();
        $filters->applyOrderScope($ordersQuery);

        return [
            'total_cases' => $this->platformCaseCount($filters),
            'total_orders' => $ordersQuery->count(),
            'confirmed_orders' => (clone $ordersQuery)->where('status', OrderStatus::Confirmed)->count(),
            'pending_orders' => (clone $ordersQuery)->where('status', OrderStatus::Pending)->count(),
        ];
    }

    public function client(Request $request): View
    {
        $user = auth()->user();
        $company = $user->company;
        $dashboardFilters = DashboardFilters::fromRequest($request);

        $caseScope = $company
            ? fn (Builder $q) => $q->where('company_id', $company->id)
            : null;

        $ordersQuery = $company
            ? Order::query()->where('company_id', $company->id)
            : Order::query()->whereRaw('1 = 0');
        $dashboardFilters->applyDateScope($ordersQuery);

        $reportsQuery = Report::query()
            ->whereNotNull('delivered_at')
            ->whereHas('caseFile', fn (Builder $q) => $q->where('company_id', $company?->id));
        $dashboardFilters->applyReportDateScope($reportsQuery);

        $stats = [
            'orders' => $company ? $ordersQuery->count() : 0,
            'confirmed_orders' => $company
                ? (clone $ordersQuery)->where('status', OrderStatus::Confirmed)->count()
                : 0,
            'cases' => $this->companyCaseCount($caseScope, $dashboardFilters),
            'reports' => $company ? $reportsQuery->count() : 0,
        ];

        $charts = DashboardChartData::forClient($company?->id, $dashboardFilters);

        return view('client.dashboard', compact('stats', 'company', 'user', 'charts', 'dashboardFilters'));
    }

    public function employee(Request $request): View
    {
        $user = auth()->user();
        $userId = $user->id;
        $dashboardFilters = DashboardFilters::fromRequest($request);
        $caseScope = fn (Builder $q) => $q->forAnalyst($userId);

        $reportsBase = Report::query()->whereHas('caseFile', fn (Builder $q) => $q->forAnalyst($userId));
        $dashboardFilters->applyReportDateScope($reportsBase);

        $stats = [
            'active_cases' => $this->filteredCaseCount($caseScope, $dashboardFilters, true),
            'reports_delivered' => (clone $reportsBase)->whereNotNull('delivered_at')->count(),
        ];

        $charts = DashboardChartData::forAnalyst($userId, $dashboardFilters);

        $viewPrefix = $user->role->value;

        return view($viewPrefix.'.dashboard', [
            'stats' => $stats,
            'charts' => $charts,
            'dashboardFilters' => $dashboardFilters,
            'portalTitle' => $user->role->label().' dashboard',
        ]);
    }

    private function platformCaseCount(DashboardFilters $filters): int
    {
        $query = CaseFile::query();
        $filters->applyCaseScope($query);

        return $query->count();
    }

    private function companyCaseCount(?callable $scope, DashboardFilters $filters): int
    {
        $query = CaseFile::query();
        if ($scope) {
            $scope($query);
        }
        $filters->applyCaseScope($query);

        return $query->count();
    }

    private function filteredCaseCount(?callable $scope, DashboardFilters $filters, bool $assignedWorkload = false): int
    {
        $query = CaseFile::query();
        if ($scope) {
            $scope($query);
        }
        if ($assignedWorkload) {
            $filters->applyAssignedCaseScopeWithPeriod($query);
        } else {
            $filters->applyCaseScope($query);
        }

        return $query->count();
    }
}
