<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\Report;
use App\Notifications\ReportReadyNotification;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use App\Enums\UserRole;
use App\Support\CaseWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\PortalRoute;
use App\Support\Toast;
use Illuminate\Support\Facades\Notification;

class ReportController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private PublicUploadService $uploads,
    ) {}

    public function store(Request $request, CaseFile $case): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $isStaff = $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin);

        if (! $isStaff) {
            abort_unless($case->hasAnalyst($user->id), 403);
            abort_unless($user->role === UserRole::Fqa, 403);

            $case->loadMissing('stage');
            if ($case->stage?->slug !== CaseWorkflow::SLUG_FQA_STARTED) {
                return Toast::back('Report can be sent only after FQA has started.');
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'string'],
            'documents' => ['nullable', 'array', 'min:1'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.data' => ['required_with:documents', 'string'],
            'is_password_protected' => ['nullable', 'boolean'],
            'file_password' => ['required_if:is_password_protected,1', 'nullable', 'string', 'max:100'],
        ]);

        $docs = $data['documents'] ?? null;
        if (! $docs) {
            if (empty($data['name']) || empty($data['data'])) {
                return Toast::back('Please select at least one report file.');
            }
            $docs = [['name' => $data['name'], 'data' => $data['data']]];
        }

        $created = [];
        foreach ($docs as $doc) {
            $originalName = $doc['name'];
            $binary = $this->uploads->decodeBase64($doc['data']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($ext === 'zip') {
                $path = $this->uploads->storeBinary($binary, $originalName, 'reports', $case->id);
                $created[] = Report::create([
                    'case_id' => $case->id,
                    'uploaded_by' => auth()->id(),
                    'title' => count($docs) > 1 ? ($data['title'].' — '.$originalName) : $data['title'],
                    'original_name' => $originalName,
                    'path' => $path,
                    'mime_type' => null,
                    'is_password_protected' => $request->boolean('is_password_protected'),
                    'file_password' => $request->boolean('is_password_protected') ? $data['file_password'] : null,
                    'delivered_at' => now(),
                ]);
                continue;
            }

            $path = $this->uploads->storeBinary($binary, $originalName, 'reports', $case->id);
            $created[] = Report::create([
                'case_id' => $case->id,
                'uploaded_by' => auth()->id(),
                'title' => count($docs) > 1 ? ($data['title'].' — '.$originalName) : $data['title'],
                'original_name' => $originalName,
                'path' => $path,
                'mime_type' => null,
                'is_password_protected' => $request->boolean('is_password_protected'),
                'file_password' => $request->boolean('is_password_protected') ? $data['file_password'] : null,
                'delivered_at' => now(),
            ]);
        }

        foreach ($created as $report) {
            $this->audit->log('report.delivered', $report);
        }

        $case->load('company.users');
        $clientUsers = $case->company->users;
        if (! empty($created)) {
            Notification::send($clientUsers, new ReportReadyNotification($created[array_key_last($created)]));
        }

        $sentStage = \App\Models\WorkflowStage::where('slug', CaseWorkflow::SLUG_SENT_TO_CLIENT)->first();
        if ($sentStage) {
            $case->update(['workflow_stage_id' => $sentStage->id]);
            \App\Models\CaseStageHistory::create([
                'case_id' => $case->id,
                'workflow_stage_id' => $sentStage->id,
                'user_id' => auth()->id(),
                'notes' => 'Report delivered to client.',
            ]);
        }

        $redirect = $isStaff
            ? route(($user->hasRole(UserRole::SuperAdmin) ? 'superadmin' : 'admin').'.cases.show', $case)
            : PortalRoute::route('cases.show', $case);

        return Toast::to($redirect, 'Report uploaded and client notified.');
    }
}
