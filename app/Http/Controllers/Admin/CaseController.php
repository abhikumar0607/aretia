<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\CaseStageHistory;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\WorkflowStage;
use App\Enums\EmployeeType;
use App\Services\AuditService;
use App\Services\CaseLinkService;
use App\Services\CaseTeamAssignmentService;
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
        private AuditService $audit,
        private CaseLinkService $caseLinks,
        private CaseTeamAssignmentService $teamAssignment,
        private CaseOrderDocumentService $caseDocuments,
    ) {}

    public function index(Request $request): View
    {
        $cases = CaseFile::query()
            ->with(array_merge(
                ['company', 'order.package', 'stage', 'assignee', 'analysts'],
                CaseFile::clientContactWith()
            ))
            ->tap(fn ($query) => CaseListFilters::apply($query, $request))
            ->latest()
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $stageOptions = CaseListFilters::stageOptions();
        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('admin.cases.index', [
            'cases' => $cases,
            'stageOptions' => $stageOptions,
            'companyOptions' => $companyOptions,
            'enableCaseLinking' => true,
            'linkCasesRoute' => route('admin.cases.link'),
        ]);
    }

    public function linkRelated(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'case_ids' => ['required', 'array', 'min:2'],
            'case_ids.*' => ['integer', 'exists:cases,id'],
        ]);

        $this->caseLinks->linkCases($data['case_ids'], $request->user());

        return Toast::to(route('admin.cases.index'), 'Selected cases are now linked as related.');
    }

    public function show(CaseFile $case): View
    {
        $this->caseDocuments->syncFromOrder($case);

        $case->load(array_merge(
            ['company', 'order.package', 'stage', 'assignee', 'analysts', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'latestReport'],
            CaseFile::clientContactWith()
        ));
        $employeesByType = User::employees()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (User $user) => $user->role->value);

        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();
        $relatedCases = $this->caseLinks->relatedCasesFor($case);
        $teamByType = $case->teamByEmployeeType();

        return view('admin.cases.show', compact('case', 'employeesByType', 'stages', 'relatedCases', 'teamByType'));
    }

    public function assign(Request $request, CaseFile $case): RedirectResponse|JsonResponse
    {
        try {
            $memberIdsByType = $this->teamAssignment->validateTeamPayload($request->input('team', []));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Toast::back($e->validator->errors()->first() ?? 'Assign Analyst, QA, and FQA for this case.');
        }

        $team = $this->teamAssignment->assign($case, $memberIdsByType, (int) auth()->id());
        $case->loadMissing('assignee');
        $leadId = (int) ($case->assignee?->id ?? 0);

        $this->audit->log('case.assigned', $case, [
            'assigned_to' => $leadId,
            'analyst_ids' => $team->pluck('id')->all(),
            'team_roles' => $team->mapWithKeys(fn (User $user) => [
                $user->role->value => $user->name,
            ])->all(),
        ]);

        return Toast::to(route('admin.cases.show', $case), 'Case team assigned.');
    }

    public function updateStage(Request $request, CaseFile $case): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'workflow_stage_id' => ['required', 'exists:workflow_stages,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $case->update(['workflow_stage_id' => $data['workflow_stage_id']]);

        CaseStageHistory::create([
            'case_id' => $case->id,
            'workflow_stage_id' => $data['workflow_stage_id'],
            'user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log('case.stage_updated', $case, $data);

        return Toast::to(route('admin.cases.show', $case), 'Case stage updated.');
    }
}
