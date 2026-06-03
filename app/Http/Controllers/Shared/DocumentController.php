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
            'category' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'string'],
            'documents' => ['nullable', 'array', 'min:1'],
            'documents.*.name' => ['required_with:documents', 'string', 'max:255'],
            'documents.*.data' => ['required_with:documents', 'string'],
        ]);

        $category = $data['category'] ?? 'general';

        $docs = $data['documents'] ?? null;
        if (! $docs) {
            if (empty($data['name']) || empty($data['data'])) {
                return Toast::back('Please select at least one file.');
            }
            $docs = [['name' => $data['name'], 'data' => $data['data']]];
        }

        foreach ($docs as $doc) {
            $originalName = $doc['name'];
            $binary = $this->uploads->decodeBase64($doc['data']);

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($ext === 'zip') {
                $path = $this->uploads->storeBinary($binary, $originalName, 'cases', $case->id);
                Document::create([
                    'documentable_type' => CaseFile::class,
                    'documentable_id' => $case->id,
                    'uploaded_by' => auth()->id(),
                    'type' => 'uploaded',
                    'category' => $category,
                    'original_name' => $originalName,
                    'path' => $path,
                ]);

                continue;
            }

            $path = $this->uploads->storeBinary($binary, $originalName, 'cases', $case->id);
            Document::create([
                'documentable_type' => CaseFile::class,
                'documentable_id' => $case->id,
                'uploaded_by' => auth()->id(),
                'type' => 'uploaded',
                'category' => $category,
                'original_name' => $originalName,
                'path' => $path,
            ]);
        }

        $this->audit->log('document.uploaded', $case);

        return Toast::to($this->caseShowUrl($case), 'Document(s) uploaded successfully.');
    }

    public function preview(Document $document): BinaryFileResponse
    {
        $this->authorizeDocument($document);

        $full = $this->uploads->absolutePath($document->path);
        abort_unless(is_file($full), 404);

        return response()->file($full);
    }

    public function download(Document $document): BinaryFileResponse
    {
        $this->authorizeDocument($document);

        $this->audit->log('document.downloaded', $document);

        return $this->uploads->download($document->path, $document->original_name);
    }

    private function authorizeDocument(Document $document): void
    {
        $case = $document->documentable;
        if ($case instanceof CaseFile) {
            $this->authorizeCaseAccess($case);

            return;
        }

        abort(404);
    }

    private function caseShowUrl(CaseFile $case): string
    {
        $role = auth()->user()->role;
        if ($role instanceof UserRole) {
            $role = $role->value;
        }

        if (UserRole::tryFrom($role)?->isEmployeeRole()) {
            return \App\Support\PortalRoute::route('cases.show', $case, true, auth()->user());
        }

        $routeName = match ($role) {
            UserRole::Client->value => 'client.cases.show',
            UserRole::SuperAdmin->value => 'superadmin.cases.show',
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

        if ($user->isEmployee() && $case->hasAnalyst($user)) {
            return;
        }

        abort(403);
    }
}
