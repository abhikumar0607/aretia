<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\CaseStageHistory;
use App\Models\WorkflowStage;
use App\Services\AuditService;
use App\Services\CaseOrderDocumentService;
use App\Services\CaseStageCompletionNotifyService;
use App\Support\CompanyFilter;
use App\Support\CaseWorkflow;
use App\Support\CaseListFilters;
use App\Support\PortalRoute;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private CaseOrderDocumentService $caseDocuments,
        private CaseStageCompletionNotifyService $stageCompletionNotify,
    ) {}

    public function index(Request $request): View
    {
        $cases = CaseFile::forAnalyst(auth()->id())
            ->with(array_merge(['company', 'order.package', 'stage', 'analysts'], CaseFile::clientContactWith()))
            ->tap(fn ($query) => CaseListFilters::apply($query, $request))
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $stageOptions = CaseListFilters::stageOptions();
        $packageOptions = CaseListFilters::packageOptions();
        $companyOptions = CompanyFilter::optionsForUser($request->user());

        $viewPrefix = auth()->user()->role->value;

        return view($viewPrefix.'.cases.index', compact('cases', 'stageOptions', 'packageOptions', 'companyOptions'));
    }

    public function show(CaseFile $case): View
    {
        abort_unless($case->hasAnalyst(auth()->id()), 403);

        $this->caseDocuments->syncFromOrder($case);

        $case->load(array_merge(
            ['company', 'order.package', 'stage', 'assignee', 'analysts', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'comments.user', 'latestReport', 'report.uploader'],
            CaseFile::clientContactWith()
        ));
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();
        $role = auth()->user()->role;
        $currentSlug = CaseWorkflow::normalizeCurrentSlug($case->stage?->slug);
        $dropdownSlugs = CaseWorkflow::employeeDropdownSlugs($role, $currentSlug);
        $selectableSlugs = CaseWorkflow::employeeSelectableTargetSlugs($role, $currentSlug);

        $dropdownStageIds = $stages
            ->filter(fn (WorkflowStage $stage) => in_array($stage->slug, $dropdownSlugs, true))
            ->pluck('id')
            ->all();
        $selectableStageIds = $stages
            ->filter(fn (WorkflowStage $stage) => in_array($stage->slug, $selectableSlugs, true))
            ->pluck('id')
            ->all();
        $stageFrozen = CaseWorkflow::employeeLaneFrozen($role, $currentSlug);
        $canUpdateStage = count(array_diff($selectableSlugs, [$currentSlug])) > 0;
        $defaultStageId = in_array((int) $case->workflow_stage_id, $dropdownStageIds, true)
            ? $case->workflow_stage_id
            : ($dropdownStageIds[0] ?? null);

        $viewPrefix = auth()->user()->role->value;

        return view($viewPrefix.'.cases.show', compact(
            'case',
            'stages',
            'dropdownStageIds',
            'selectableStageIds',
            'stageFrozen',
            'canUpdateStage',
            'defaultStageId',
        ));
    }

    public function updateStage(Request $request, CaseFile $case): JsonResponse|RedirectResponse
    {
        abort_unless($case->hasAnalyst(auth()->id()), 403);

        $data = $request->validate([
            'workflow_stage_id' => ['required', 'exists:workflow_stages,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $case->loadMissing('stage');
        $currentSlug = CaseWorkflow::normalizeCurrentSlug($case->stage?->slug);
        $targetStage = WorkflowStage::findOrFail((int) $data['workflow_stage_id']);

        $selectableSlugs = CaseWorkflow::employeeSelectableTargetSlugs(auth()->user()->role, $currentSlug);
        if (! in_array($targetStage->slug, $selectableSlugs, true)) {
            return Toast::back('You are not allowed to move the case to this stage yet.');
        }

        $case->update(['workflow_stage_id' => $data['workflow_stage_id']]);

        CaseStageHistory::create([
            'case_id' => $case->id,
            'workflow_stage_id' => $data['workflow_stage_id'],
            'user_id' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log('case.stage_updated', $case, $data);

        $this->stageCompletionNotify->notifyIfCompleted(
            $case,
            $request->user(),
            $currentSlug,
            $targetStage->slug,
        );

        return Toast::to(PortalRoute::route('cases.show', $case), 'Stage updated successfully.');
    }
}
