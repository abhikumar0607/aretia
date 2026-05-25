<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\CaseStageHistory;
use App\Models\WorkflowStage;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $cases = CaseFile::where('assigned_to', auth()->id())
            ->with(array_merge(['company', 'order.package', 'stage'], CaseFile::clientContactWith()))
            ->latest()
            ->paginate(config('portal.per_page'));

        return view('analyst.cases.index', compact('cases'));
    }

    public function show(CaseFile $case): View
    {
        abort_unless($case->assigned_to === auth()->id(), 403);

        $case->load(array_merge(
            ['company', 'order.package', 'stage', 'assignee', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'latestReport'],
            CaseFile::clientContactWith()
        ));
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();

        return view('analyst.cases.show', compact('case', 'stages'));
    }

    public function updateStage(Request $request, CaseFile $case): JsonResponse|RedirectResponse
    {
        abort_unless($case->assigned_to === auth()->id(), 403);

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

        return Toast::to(route('analyst.cases.show', $case), 'Stage updated successfully.');
    }
}
