<?php

namespace App\Http\Controllers\Shared;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private PublicUploadService $uploads,
    ) {}

    /** Saves to public/uploads/cases/{case_id}/ — validated in controller. */
    public function store(Request $request, CaseFile $case): JsonResponse|RedirectResponse
    {
        $this->authorizeCaseAccess($case);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $binary = $this->uploads->decodeBase64($data['data']);
        $path = $this->uploads->storeBinary($binary, $data['name'], 'cases', $case->id);

        Document::create([
            'documentable_type' => CaseFile::class,
            'documentable_id' => $case->id,
            'uploaded_by' => auth()->id(),
            'type' => 'uploaded',
            'category' => $data['category'] ?? 'general',
            'original_name' => $data['name'],
            'path' => $path,
        ]);

        $this->audit->log('document.uploaded', $case);

        return Toast::to($this->caseShowUrl($case), 'Document uploaded successfully.');
    }

    public function download(Document $document): BinaryFileResponse
    {
        $case = $document->documentable;
        if ($case instanceof CaseFile) {
            $this->authorizeCaseAccess($case);
        }

        $this->audit->log('document.downloaded', $document);

        return $this->uploads->download($document->path, $document->original_name);
    }

    private function caseShowUrl(CaseFile $case): string
    {
        $role = auth()->user()->role;
        if ($role instanceof UserRole) {
            $role = $role->value;
        }

        $routeName = match ($role) {
            UserRole::Client->value => 'client.cases.show',
            UserRole::Analyst->value => 'analyst.cases.show',
            default => 'admin.cases.show',
        };

        return route($routeName, $case);
    }

    private function authorizeCaseAccess(CaseFile $case): void
    {
        $user = auth()->user();

        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            return;
        }

        if ($user->hasRole(UserRole::Client) && $case->company_id === $user->company_id) {
            return;
        }

        if ($user->hasRole(UserRole::Analyst) && $case->hasAnalyst($user)) {
            return;
        }

        abort(403);
    }
}
