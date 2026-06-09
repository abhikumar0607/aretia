<?php

namespace App\Http\Controllers\Shared;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportAccessService;
use App\Support\CompanyFilter;
use App\Support\ReportListFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffReportController extends Controller
{
    public function __construct(private ReportAccessService $access) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->access->canViewReportsList($user), 403);

        $query = Report::query()
            ->with(['caseFile.company', 'caseFile.order.package', 'uploader'])
            ->whereNotNull('delivered_at')
            ->latest('delivered_at');

        $this->scopeReportsForUser($query, $user);

        ReportListFilters::apply($query, $request);

        $reports = $query->paginate(config('portal.per_page'))->withQueryString();

        $statsQuery = Report::query()
            ->whereNotNull('delivered_at');
        $this->scopeReportsForUser($statsQuery, $user);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'month' => (clone $statsQuery)
                ->where('delivered_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $companyOptions = CompanyFilter::optionsForUser($user);
        $routePrefix = $this->routePrefix();

        return view($routePrefix.'.reports.index', compact('reports', 'stats', 'companyOptions', 'routePrefix'));
    }

    public function show(Report $report): View
    {
        abort_unless($this->access->canViewDelivered($report, auth()->user()), 403);

        $report->load(['caseFile.company', 'caseFile.order.package', 'uploader']);

        return view($this->routePrefix().'.reports.show', [
            'report' => $report,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    private function scopeReportsForUser(Builder $query, $user): void
    {
        if ($user->isEmployee()) {
            $query->whereHas('caseFile', fn (Builder $case) => $case->forAnalyst($user));
        }
    }

    private function routePrefix(): string
    {
        $user = auth()->user();

        if ($user->hasRole(UserRole::SuperAdmin)) {
            return 'superadmin';
        }

        if ($user->hasRole(UserRole::Admin)) {
            return 'admin';
        }

        return $user->role->value;
    }
}
