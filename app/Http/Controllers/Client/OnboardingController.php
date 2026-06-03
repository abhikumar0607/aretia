<?php

namespace App\Http\Controllers\Client;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\User;
use App\Notifications\OnboardingReviewRequestedNotification;
use App\Services\AuditService;
use App\Services\PublicUploadService;
use App\Rules\StrictEmail;
use App\Support\KycDocumentLabels;
use App\Support\Toast;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OnboardingController extends Controller
{
    private const ALLOWED_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'zip'];

    public function __construct(
        private AuditService $audit,
        private PublicUploadService $uploads,
    ) {}

    public function show(): View
    {
        $company = auth()->user()->company;
        $documents = $company?->kycDocuments()->latest()->get() ?? collect();

        $hasGovernmentId = $documents->where('type', 'national_id')->isNotEmpty();
        $hasRegistration = $documents->where('type', 'incorporation')->isNotEmpty();
        $canSubmit = $hasGovernmentId || $hasRegistration;
        $canReopen = $company && $company->status === CompanyStatus::KycSubmitted;

        return view('client.onboarding', compact(
            'company',
            'documents',
            'hasGovernmentId',
            'hasRegistration',
            'canSubmit',
            'canReopen',
        ));
    }

    public function account(): View|RedirectResponse
    {
        $user = auth()->user();
        $company = $user->company;

        if (! $company || $company->status === CompanyStatus::Active) {
            return redirect()->route('client.onboarding');
        }

        if ($company->status === CompanyStatus::KycSubmitted) {
            return redirect()->route('client.onboarding');
        }

        return view('client.onboarding-account', compact('user', 'company'));
    }

    public function updateAccount(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $company = $user->company;

        if (! $company || in_array($company->status, [CompanyStatus::Active, CompanyStatus::KycSubmitted], true)) {
            return Toast::to(route('client.onboarding'), 'Your account details can no longer be edited here.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'max:255', new StrictEmail, Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $company->update([
            'name' => $data['company'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);

        $this->audit->log('client.account_updated', $company, ['user_id' => $user->id]);

        return Toast::to(route('client.onboarding'), 'Account details updated. You can continue uploading documents.');
    }

    /** Upload one or more files for a document type (saved immediately). */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $type = $request->input('type');

        $request->validate([
            'type' => ['required', 'in:national_id,incorporation'],
            'subtype' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'data' => ['required', 'string'],
        ]);

        $user = auth()->user();
        $company = $user->company;

        if ($company->status === CompanyStatus::KycSubmitted) {
            return Toast::back('Your application is under review. Use “Edit documents” to make changes.');
        }

        if ($company->status === CompanyStatus::Active) {
            return Toast::back('Your account is already active.');
        }

        $subtype = $request->input('subtype') ?: null;

        $this->saveDocument(
            $company->id,
            $user->id,
            $type,
            $request->input('name'),
            $request->input('data'),
            $subtype
        );

        $label = $type === 'national_id' ? 'Government ID' : 'Company registration';

        return Toast::to(route('client.onboarding'), "{$label} uploaded successfully.");
    }

    public function submit(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $company = $user->company;

        if ($company->status === CompanyStatus::KycSubmitted) {
            return Toast::to(route('client.onboarding'), 'Your documents are already submitted for review.');
        }

        if ($company->status === CompanyStatus::Active) {
            return Toast::back('Your account is already active.');
        }

        if (! $this->hasAtLeastOneDocument($company->id)) {
            return Toast::back('Please upload at least one file — Government ID and/or Company Registration documents.');
        }

        $company->update([
            'status' => CompanyStatus::KycSubmitted,
            'rejection_reason' => null,
        ]);
        $user->update(['onboarding_status' => OnboardingStatus::KycSubmitted]);
        $this->audit->log('kyc.submitted', $company);

        $reviewers = User::whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])->get();
        if ($reviewers->isNotEmpty()) {
            Notification::send($reviewers, new OnboardingReviewRequestedNotification($company));
        }

        return Toast::to(route('client.onboarding'), 'Documents submitted. We will notify you once approved.');
    }

    /** Preview (inline) or download a KYC file owned by the client's company. */
    public function document(Request $request, KycDocument $kyc): BinaryFileResponse
    {
        $this->authorizeKycDocument($kyc);

        if ($request->boolean('download')) {
            return $this->uploads->download($kyc->path, $kyc->original_name);
        }

        $full = $this->uploads->absolutePath($kyc->path);
        abort_unless(is_file($full), 404);

        return response()->file($full);
    }

    /** Roll back to upload step so the client can change documents. */
    public function reopen(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $company = $user->company;

        if ($company->status !== CompanyStatus::KycSubmitted) {
            return Toast::back('You can upload or update documents from this page.');
        }

        $company->update(['status' => CompanyStatus::Pending]);
        $user->update(['onboarding_status' => OnboardingStatus::Registered]);

        return Toast::to(route('client.onboarding'), 'You can update your documents and submit again when ready.');
    }

    private function authorizeKycDocument(KycDocument $kyc): void
    {
        $company = auth()->user()->company;
        abort_unless($company && $kyc->company_id === $company->id, 403);
    }

    private function hasAtLeastOneDocument(int $companyId): bool
    {
        return KycDocument::where('company_id', $companyId)
            ->whereIn('type', ['national_id', 'incorporation'])
            ->exists();
    }

    private function saveDocument(int $companyId, int $userId, string $type, string $originalName, string $base64, ?string $subtype): void
    {
        $binary = $this->uploads->decodeBase64($base64);

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($ext, self::ALLOWED_EXT, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data' => 'Only PDF, JPG, PNG, and ZIP are allowed.',
            ]);
        }

        if ($ext === 'zip') {
            $relativePath = $this->uploads->storeBinary($binary, $originalName, 'kyc', $companyId);

            KycDocument::create([
                'company_id' => $companyId,
                'uploaded_by' => $userId,
                'type' => $type,
                'subtype' => $subtype,
                'original_name' => $originalName,
                'path' => $relativePath,
                'status' => 'pending',
            ]);

            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);
        if (! in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data' => 'Invalid file type. Use PDF, JPG or PNG.',
            ]);
        }

        $relativePath = $this->uploads->storeBinary($binary, $originalName, 'kyc', $companyId);

        KycDocument::create([
            'company_id' => $companyId,
            'uploaded_by' => $userId,
            'type' => $type,
            'subtype' => $subtype,
            'original_name' => $originalName,
            'path' => $relativePath,
            'status' => 'pending',
        ]);
    }
}
