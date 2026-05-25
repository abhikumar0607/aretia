<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\Report;
use App\Notifications\ReportReadyNotification;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        abort_unless($case->hasAnalyst(auth()->id()), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'data' => ['required', 'string'],
            'is_password_protected' => ['nullable', 'boolean'],
            'file_password' => ['required_if:is_password_protected,1', 'nullable', 'string', 'max:100'],
        ]);

        $binary = $this->uploads->decodeBase64($data['data']);
        $path = $this->uploads->storeBinary($binary, $data['name'], 'reports', $case->id);

        $report = Report::create([
            'case_id' => $case->id,
            'uploaded_by' => auth()->id(),
            'title' => $data['title'],
            'original_name' => $data['name'],
            'path' => $path,
            'mime_type' => null,
            'is_password_protected' => $request->boolean('is_password_protected'),
            'file_password' => $request->boolean('is_password_protected') ? $data['file_password'] : null,
            'delivered_at' => now(),
        ]);

        $this->audit->log('report.delivered', $report);

        $case->load('company.users');
        $clientUsers = $case->company->users;
        Notification::send($clientUsers, new ReportReadyNotification($report));


        return Toast::to(route('analyst.cases.show', $case), 'Report uploaded and client notified.');
    }
}
