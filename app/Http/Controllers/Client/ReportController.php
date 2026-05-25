<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private PublicUploadService $uploads,
    ) {}

    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        $query = Report::whereHas('caseFile', fn ($q) => $q->where('company_id', $companyId))
            ->with(['caseFile.order.package'])
            ->whereNotNull('delivered_at')
            ->latest('delivered_at');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('caseFile', fn ($c) => $c->where('reference', 'like', "%{$search}%"));
            });
        }

        $reports = $query->paginate(config('portal.per_page'))->withQueryString();

        $stats = [
            'total' => Report::whereHas('caseFile', fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('delivered_at')->count(),
            'month' => Report::whereHas('caseFile', fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('delivered_at')
                ->where('delivered_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        return view('client.reports.index', compact('reports', 'stats'));
    }

    public function show(Report $report): View
    {
        $this->authorizeReport($report);

        return view('client.reports.show', compact('report'));
    }

    public function download(Request $request, Report $report): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeReport($report);

        if ($report->is_password_protected) {
            $request->validate(['file_password' => ['required', 'string']]);
            if ($request->file_password !== $report->file_password) {
                return back()->withErrors(['file_password' => 'Incorrect file password.']);
            }
        }

        $report->update(['downloaded_at' => now()]);
        $this->audit->log('report.downloaded', $report);

        return $this->uploads->download($report->path, $report->original_name);
    }

    private function authorizeReport(Report $report): void
    {
        abort_unless(
            $report->caseFile->company_id === auth()->user()->company_id && $report->delivered_at,
            403
        );
    }
}
