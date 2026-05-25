<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardChartData;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function superadmin(): View
    {
        $stats = $this->adminStats();
        $charts = DashboardChartData::forSuperAdmin();

        return view('superadmin.dashboard', compact('stats', 'charts'));
    }

    public function admin(): View
    {
        return $this->adminDashboard('admin.dashboard');
    }

    private function adminDashboard(string $view): View
    {
        $stats = $this->adminStats();
        $charts = DashboardChartData::forAdmin();

        return view($view, compact('stats', 'charts'));
    }

    /**
     * @return array{client_companies: int, active_clients: int, analysts: int, pending_onboarding: int, orders: int, open_cases: int, reports_ready: int}
     */
    private function adminStats(): array
    {
        return [
            'client_companies' => Company::count(),
            'active_clients' => Company::where('status', CompanyStatus::Active)->count(),
            'analysts' => User::where('role', UserRole::Analyst)->count(),
            'pending_onboarding' => Company::whereIn('status', [CompanyStatus::Pending, CompanyStatus::KycSubmitted])->count(),
            'orders' => Order::count(),
            'open_cases' => CaseFile::where('status', 'open')->count(),
            'reports_ready' => Report::whereNotNull('delivered_at')->count(),
        ];
    }

    public function client(): View
    {
        $user = auth()->user();
        $company = $user->company;

        $stats = [
            'orders' => $company?->orders()->count() ?? 0,
            'cases' => $company?->cases()->count() ?? 0,
            'reports' => Report::whereHas('caseFile', fn ($q) => $q->where('company_id', $company?->id))->whereNotNull('delivered_at')->count(),
        ];

        $packages = \App\Models\ServicePackage::where('is_active', true)->where('is_custom', false)->orderBy('sort_order')->limit(4)->get();

        $charts = DashboardChartData::forClient($company?->id);

        return view('client.dashboard', compact('stats', 'packages', 'company', 'user', 'charts'));
    }

    public function analyst(): View
    {
        $userId = auth()->id();

        $stats = [
            'assigned_cases' => CaseFile::forAnalyst($userId)->count(),
            'in_progress' => CaseFile::forAnalyst($userId)->whereHas('stage', fn ($q) => $q->where('slug', 'in-progress'))->count(),
            'completed' => CaseFile::forAnalyst($userId)->whereHas('stage', fn ($q) => $q->where('slug', 'completed'))->count(),
        ];

        $cases = CaseFile::forAnalyst(auth()->id())
            ->with(array_merge(['company', 'stage'], CaseFile::clientContactWith()))
            ->latest()
            ->limit(5)
            ->get();

        $charts = DashboardChartData::forAnalyst(auth()->id());

        return view('analyst.dashboard', compact('stats', 'cases', 'charts'));
    }
}
