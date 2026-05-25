<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\WorkflowStage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        $query = CaseFile::where('company_id', $companyId)
            ->with(['order.package', 'stage', 'assignee', 'latestReport'])
            ->latest();

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('subject_name', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%"));
            });
        }

        if ($stage = $request->input('stage')) {
            $query->where('workflow_stage_id', $stage);
        }

        $cases = $query->paginate(config('portal.per_page'))->withQueryString();

        $stages = WorkflowStage::where('is_active', true)->orderBy('sort_order')->get();

        $stats = [
            'total' => CaseFile::where('company_id', $companyId)->count(),
            'in_progress' => CaseFile::where('company_id', $companyId)
                ->whereDoesntHave('latestReport', fn ($q) => $q->whereNotNull('delivered_at'))
                ->count(),
            'completed' => CaseFile::where('company_id', $companyId)
                ->whereHas('latestReport', fn ($q) => $q->whereNotNull('delivered_at'))
                ->count(),
        ];

        $stageOptions = $stages->pluck('name', 'id')->all();

        return view('client.cases.index', compact('cases', 'stats', 'stageOptions'));
    }

    public function show(CaseFile $case): View
    {
        abort_unless($case->company_id === auth()->user()->company_id, 403);

        $case->load(['company', 'order.package', 'stage', 'assignee', 'stageHistories.stage', 'stageHistories.user', 'messages.sender', 'documents.uploader', 'latestReport']);

        return view('client.cases.show', compact('case'));
    }
}
