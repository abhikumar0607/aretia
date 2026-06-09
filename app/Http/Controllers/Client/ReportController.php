<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportAccessService;
use App\Support\CompanyFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportAccessService $access) {}

    public function index(Request $request): View
    {
        $companyIds = CompanyFilter::scopedCompanyIdsForUser($request->user());

        $query = Report::whereHas('caseFile', fn ($q) => $q->whereIn('company_id', $companyIds))
            ->with(['caseFile.company', 'caseFile.order.package'])
            ->whereNotNull('delivered_at')
            ->latest('delivered_at');

        if ($request->filled('company')) {
            $filterIds = CompanyFilter::equivalentCompanyIds((int) $request->input('company'));
            $query->whereHas('caseFile', fn ($q) => $q->whereIn('company_id', $filterIds));
        }

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('caseFile', fn ($c) => $c->where('reference', 'like', "%{$search}%"));
            });
        }

        $reports = $query->paginate(config('portal.per_page'))->withQueryString();

        $stats = [
            'total' => Report::whereHas('caseFile', fn ($q) => $q->whereIn('company_id', $companyIds))
                ->whereNotNull('delivered_at')->count(),
            'month' => Report::whereHas('caseFile', fn ($q) => $q->whereIn('company_id', $companyIds))
                ->whereNotNull('delivered_at')
                ->where('delivered_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('client.reports.index', compact('reports', 'stats', 'companyOptions'));
    }

    public function show(Report $report): View
    {
        $this->authorizeReport($report);

        return view('client.reports.show', compact('report'));
    }

    private function authorizeReport(Report $report): void
    {
        abort_unless($this->access->canViewDelivered($report, auth()->user()), 403);
    }
}
