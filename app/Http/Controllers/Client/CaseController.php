<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Services\CaseLinkService;
use App\Services\CaseOrderDocumentService;
use App\Support\CaseListFilters;
use App\Support\CompanyFilter;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function __construct(
        private CaseLinkService $caseLinks,
        private CaseOrderDocumentService $caseDocuments,
    ) {}

    public function index(Request $request): View
    {
        $companyIds = CompanyFilter::scopedCompanyIdsForUser($request->user());

        $query = CaseFile::whereIn('company_id', $companyIds)
            ->with(['company', 'order.package', 'stage', 'assignee', 'latestReport'])
            ->tap(fn ($q) => CaseListFilters::apply($q, $request))
            ->latest();

        $cases = $query->paginate(config('portal.per_page'))->withQueryString();

        $stats = [
            'total' => CaseFile::whereIn('company_id', $companyIds)->count(),
            'in_progress' => CaseFile::whereIn('company_id', $companyIds)
                ->whereDoesntHave('latestReport', fn ($q) => $q->whereNotNull('delivered_at'))
                ->count(),
            'completed' => CaseFile::whereIn('company_id', $companyIds)
                ->whereHas('latestReport', fn ($q) => $q->whereNotNull('delivered_at'))
                ->count(),
        ];

        $stageOptions = CaseListFilters::stageOptions();
        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('client.cases.index', [
            'cases' => $cases,
            'stats' => $stats,
            'stageOptions' => $stageOptions,
            'companyOptions' => $companyOptions,
            'enableCaseLinking' => true,
            'linkCasesRoute' => route('client.cases.link'),
        ]);
    }

    public function linkRelated(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'case_ids' => ['required', 'array', 'min:2'],
            'case_ids.*' => ['integer', 'exists:cases,id'],
        ]);

        $this->caseLinks->linkCases($data['case_ids'], $request->user());

        return Toast::to(route('client.cases.index'), 'Selected cases are now linked as related.');
    }

    public function show(CaseFile $case): View
    {
        CompanyFilter::authorizeCompanyAccess($case->company_id);

        $this->caseDocuments->syncFromOrder($case);

        $case->load(['company', 'order.package', 'stage', 'assignee', 'analysts', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'latestReport']);
        $relatedCases = $this->caseLinks->relatedCasesFor($case);

        return view('client.cases.show', compact('case', 'relatedCases'));
    }
}
