<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowStage;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkflowStageController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $stages = WorkflowStage::orderBy('sort_order')->withCount('cases')->get();

        return view('superadmin.workflow.index', compact('stages'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'responsible_role' => ['nullable', 'in:analyst,qa,fqa'],
        ]);

        $slug = Str::slug($data['name']);
        $maxOrder = WorkflowStage::max('sort_order') ?? 0;

        $stage = WorkflowStage::create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? '#64748b',
            'sort_order' => $maxOrder + 1,
            'responsible_role' => $data['responsible_role'] ?? null,
        ]);

        $this->audit->log('workflow_stage.created', $stage);

        return Toast::back('Workflow stage added.');
    }

    public function updateResponsible(Request $request, WorkflowStage $stage): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'responsible_role' => ['nullable', 'in:analyst,qa,fqa'],
        ]);

        $stage->update(['responsible_role' => $data['responsible_role'] ?? null]);
        $this->audit->log('workflow_stage.responsible_updated', $stage, [
            'responsible_role' => $data['responsible_role'] ?? null,
        ]);

        return Toast::back('Stage owner updated.');
    }

    public function destroy(WorkflowStage $stage): JsonResponse|RedirectResponse
    {
        $stage->update(['is_active' => false]);
        $this->audit->log('workflow_stage.deactivated', $stage);

        return Toast::back('Stage deactivated.');
    }

    public function delete(WorkflowStage $stage): JsonResponse|RedirectResponse
    {
        $this->audit->log('workflow_stage.deleted', $stage);
        $stage->delete();

        return Toast::back('Stage deleted permanently.');
    }
}

