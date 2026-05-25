<?php

namespace App\Http\Controllers\Client;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png'];

    public function __construct(
        private AuditService $audit,
        private PublicUploadService $uploads,
    ) {}

    public function show(): View
    {
        $company = auth()->user()->company;
        $documents = $company?->kycDocuments()->latest()->get() ?? collect();

        return view('client.onboarding', compact('company', 'documents'));
    }

    /** Saves to public/uploads/kyc/{company_id}/ */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'in:national_id,incorporation'],
            'name' => ['required', 'string', 'max:255'],
            'data' => ['required', 'string'],
        ]);

        $user = auth()->user();
        $company = $user->company;

        $this->saveDocument(
            $company->id,
            $user->id,
            $request->input('type'),
            $request->input('name'),
            $request->input('data')
        );

        $types = KycDocument::where('company_id', $company->id)
            ->whereIn('type', ['national_id', 'incorporation'])
            ->pluck('type')
            ->unique();

        if ($types->count() >= 2) {
            $company->update(['status' => CompanyStatus::KycSubmitted]);
            $user->update(['onboarding_status' => OnboardingStatus::KycSubmitted]);
            $this->audit->log('kyc.submitted', $company);

            return Toast::to(route('client.onboarding'), 'Documents submitted. We will notify you once approved.');
        }

        return Toast::to(route('client.onboarding'), 'Document saved. Please upload the second document.');
    }

    private function saveDocument(int $companyId, int $userId, string $type, string $originalName, string $base64): void
    {
        $binary = $this->uploads->decodeBase64($base64);

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data' => 'Only PDF, JPG and PNG are allowed.',
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data' => 'Invalid file type. Use PDF, JPG or PNG.',
            ]);
        }

        $existing = KycDocument::where('company_id', $companyId)->where('type', $type)->first();
        if ($existing) {
            $this->uploads->delete($existing->path);
            $existing->delete();
        }

        $relativePath = $this->uploads->storeBinary($binary, $originalName, 'kyc', $companyId);

        KycDocument::create([
            'company_id' => $companyId,
            'uploaded_by' => $userId,
            'type' => $type,
            'original_name' => $originalName,
            'path' => $relativePath,
            'status' => 'pending',
        ]);
    }
}
