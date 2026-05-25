<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\KycDocument;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Notifications\OnboardingApprovedNotification;
use App\Notifications\OnboardingRejectedNotification;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $companies = Company::with(['users', 'kycDocuments'])
            ->whereIn('status', [CompanyStatus::Pending, CompanyStatus::KycSubmitted])
            ->latest()
            ->paginate(config('portal.per_page'));

        return view('admin.onboarding.index', compact('companies'));
    }

    public function show(Company $company): View
    {
        $company->load(['users', 'kycDocuments.uploader']);

        return view('admin.onboarding.show', compact('company'));
    }

    public function approve(Company $company): JsonResponse|RedirectResponse
    {
        $company->update([
            'status' => CompanyStatus::Active,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        $company->users()->update(['onboarding_status' => OnboardingStatus::Active]);

        $this->audit->log('onboarding.approved', $company);

        foreach ($company->users as $user) {
            Notification::send($user, new OnboardingApprovedNotification);
        }

        return Toast::back('Company approved and clients notified.');
    }

    public function reject(Request $request, Company $company): JsonResponse|RedirectResponse
    {
        $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $company->update([
            'status' => CompanyStatus::Rejected,
            'rejection_reason' => $request->rejection_reason,
        ]);

        $company->users()->update(['onboarding_status' => OnboardingStatus::Rejected]);

        $this->audit->log('onboarding.rejected', $company, ['reason' => $request->rejection_reason]);

        $company->load('users');
        foreach ($company->users as $user) {
            Notification::send($user, new OnboardingRejectedNotification($company, $request->rejection_reason));
        }

        return Toast::back('Company rejected and client notified.');
    }

    public function downloadKyc(KycDocument $kyc): BinaryFileResponse
    {
        return app(\App\Services\PublicUploadService::class)->download($kyc->path, $kyc->original_name);
    }
}
