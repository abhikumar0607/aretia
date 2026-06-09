<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use App\Services\ReportAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportDownloadController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private PublicUploadService $uploads,
        private ReportAccessService $access,
    ) {}

    public function client(Request $request, Report $report): BinaryFileResponse|RedirectResponse
    {
        $this->authorizeDelivered($report);

        if ($report->is_password_protected) {
            if (! $request->isMethod('post')) {
                return redirect()->route('client.reports.show', $report);
            }

            $data = $request->validate(['file_password' => ['required', 'string']]);
            if ($data['file_password'] !== $report->file_password) {
                throw ValidationException::withMessages([
                    'file_password' => 'Incorrect file password.',
                ]);
            }
        }

        $report->update(['downloaded_at' => now()]);
        $this->audit->log('report.downloaded', $report);

        return $this->uploads->download($report->path, $report->original_name);
    }

    public function staff(Report $report): BinaryFileResponse
    {
        $this->authorizeDelivered($report);
        abort_unless($this->access->staffDownloadsWithoutPassword(auth()->user()), 403);

        $this->audit->log('report.downloaded', $report);

        return $this->uploads->download($report->path, $report->original_name);
    }

    private function authorizeDelivered(Report $report): void
    {
        abort_unless($this->access->canViewDelivered($report, auth()->user()), 403);
    }
}
