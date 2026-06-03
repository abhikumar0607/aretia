<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\Order;
use App\Support\DashboardChartData;
use App\Support\DashboardFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dashboardFilters = DashboardFilters::fromRequest($request, 'admin');
        $stats = $this->adminStats($dashboardFilters);
        $charts = DashboardChartData::forAdmin($dashboardFilters);

        return view('admin.dashboard', compact('stats', 'charts', 'dashboardFilters'));
    }

    /**
     * @return array{client_companies: int, active_clients: int, employees: int, pending_onboarding: int, orders: int, active_cases: int, reports_ready: int}
     */
    private function adminStats(DashboardFilters $filters): array
    {
        $ordersQuery = Order::query();
        $filters->applyDateScope($ordersQuery);

        return [
            'total_cases' => $this->platformCaseCount($filters),
            'total_orders' => $ordersQuery->count(),
            'confirmed_orders' => (clone $ordersQuery)->where('status', OrderStatus::Confirmed)->count(),
            'pending_orders' => (clone $ordersQuery)->where('status', OrderStatus::Pending)->count(),
        ];
    }

    private function platformCaseCount(DashboardFilters $filters): int
    {
        $query = CaseFile::query();
        $filters->applyCaseScope($query);

        return $query->count();
    }
}

