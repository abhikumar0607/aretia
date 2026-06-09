<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\CaseStageHistory;
use App\Models\User;
use App\Models\WorkflowStage;
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
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $stageOptions = CaseListFilters::stageOptions();
        $packageOptions = CaseListFilters::packageOptions();
        $companyOptions = CompanyFilter::optionsForUser($request->user());

        return view('superadmin.cases.index', [
            'cases' => $cases,
            'stageOptions' => $stageOptions,
            'packageOptions' => $packageOptions,
            'companyOptions' => $companyOptions,
            'enableCaseLinking' => true,
            'linkCasesRoute' => route('superadmin.cases.link'),
        ]);
    }

    public function linkRelated(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'case_ids' => ['required', 'array', 'min:2'],
            'case_ids.*' => ['integer', 'exists:cases,id'],
        ]);

        $this->caseLinks->linkCases($data['case_ids'], $request->user());

        return Toast::to(route('superadmin.cases.index'), 'Selected cases are now linked as related.');
    }

    public function show(CaseFile $case): View
    {
        $this->caseDocuments->syncFromOrder($case);

        $case->load(array_merge(
            ['company', 'order.package', 'stage', 'assignee', 'analysts', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'comments.user', 'latestReport', 'report.uploader'],
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

        return view('superadmin.cases.show', compact('case', 'employeesByType', 'stages', 'relatedCases', 'teamByType'));
    }

    public function assign(Request $request, CaseFile $case): RedirectResponse|JsonResponse
    {
        try {
            $memberIdsByType = $this->teamAssignment->validateTeamPayload($request->input('team', []));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Toast::back(
                $e->validator->errors()->first() ?? 'Analyst is required.',
                'error',
                'Required'
            );
        }

        try {
            $dueDatesByRole = $this->teamAssignment->validateDueDates(
                $memberIdsByType,
                (array) $request->input('due_dates', [])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Toast::back(
                $e->validator->errors()->first() ?? 'Set a due date for each assigned role.',
                'error',
                'Required'
            );
        }

        $team = $this->teamAssignment->assign(
            $case,
            $memberIdsByType,
            (int) auth()->id(),
            $dueDatesByRole,
        );
        $case->loadMissing('assignee');
        $leadId = (int) ($case->assignee?->id ?? 0);

        $this->audit->log('case.assigned', $case, [
            'assigned_to' => $leadId,
            'analyst_ids' => $team->pluck('id')->all(),
            'team_due_dates' => $case->teamDueDatesByRole(),
            'team_roles' => $team->mapWithKeys(fn (User $user) => [
                $user->role->value => $user->name,
            ])->all(),
        ]);

        return Toast::to(route('superadmin.cases.show', $case), 'Case team assigned.');
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

        return Toast::to(route('superadmin.cases.show', $case), 'Case stage updated.');
    }
}

