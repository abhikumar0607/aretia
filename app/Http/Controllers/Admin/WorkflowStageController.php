<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkflowStage;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\Toast;
use Illuminate\View\View;

class WorkflowStageController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $stages = WorkflowStage::orderBy('sort_order')->get();

        return view('admin.workflow.index', compact('stages'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $slug = Str::slug($data['name']);
        $maxOrder = WorkflowStage::max('sort_order') ?? 0;

        $stage = WorkflowStage::create([
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? '#64748b',
            'sort_order' => $maxOrder + 1,
        ]);

        $this->audit->log('workflow_stage.created', $stage);

        return Toast::back('Workflow stage added.');
    }

    public function destroy(WorkflowStage $stage): JsonResponse|RedirectResponse
    {
        $stage->update(['is_active' => false]);
        $this->audit->log('workflow_stage.deactivated', $stage);

        return Toast::back('Stage deactivated.');
    }
}
