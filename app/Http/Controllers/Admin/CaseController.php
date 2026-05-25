<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\CaseStageHistory;
use App\Models\User;
use App\Enums\UserRole;
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
        $cases = CaseFile::with(array_merge(
            ['company', 'order.package', 'stage', 'assignee'],
            CaseFile::clientContactWith()
        ))
            ->latest()
            ->paginate(config('portal.per_page'));

        return view('admin.cases.index', compact('cases'));
    }

    public function show(CaseFile $case): View
    {
        $case->load(array_merge(
            ['company', 'order.package', 'stage', 'assignee', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'latestReport'],
            CaseFile::clientContactWith()
        ));
        $analysts = User::where('role', UserRole::Analyst)->get();
        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.cases.show', compact('case', 'analysts', 'stages'));
    }

    public function assign(Request $request, CaseFile $case): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
        ]);

        $case->update([
            'assigned_to' => $data['assigned_to'],
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
        ]);

        $this->audit->log('case.assigned', $case, ['assigned_to' => $data['assigned_to']]);

        return Toast::to(route('admin.cases.show', $case), 'Case assigned to analyst.');
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
