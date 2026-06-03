@extends('layouts.portal')
@section('title', 'Onboarding')

@php
    $status = $company?->status?->value ?? 'pending';
    $isActive = auth()->user()->onboarding_status->value === 'active';
    $kycDone = in_array($status, ['kyc_submitted', 'active']);
    $step1 = true;
    $step2 = $kycDone;
    $step3 = $status === 'kyc_submitted';
    $step4 = $isActive;
    $maxMb = \App\Services\PublicUploadService::MAX_MB;
@endphp

@section('content')

@if($isActive)
    <a href="{{ route('client.dashboard') }}" class="back-link">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to dashboard
    </a>
@elseif($canReopen ?? false)
    <form method="POST" action="{{ route('client.onboarding.reopen') }}" class="onboarding-back-form">
        @csrf
        <button type="submit" class="back-link onboarding-back-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to upload documents
        </button>
    </form>
@elseif(!$isActive && $status !== 'kyc_submitted')
    <a href="{{ route('client.onboarding.account') }}" class="back-link">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to account details
    </a>
@endif

<div class="onboarding-hero">
    <h1>Complete your onboarding</h1>
    <p>Verify your identity and company documents to activate your Aretia account.</p>

    <div class="steps-track">
        <div class="step-item {{ $step1 ? 'done' : '' }}">
            <div class="step-circle">@if($step1)✓@else 1 @endif</div>
            <span>@if($step1 && !$isActive && $status !== 'kyc_submitted')<a href="{{ route('client.onboarding.account') }}" class="step-link">Account<br>created</a>@else Account<br>created @endif</span>
        </div>
        <div class="step-item {{ $step2 ? 'done' : ($step1 && !$kycDone ? 'active' : '') }}">
            <div class="step-circle">@if($step2)✓@else 2 @endif</div>
            <span>Upload<br>KYC docs</span>
        </div>
        <div class="step-item {{ $step4 ? 'done' : ($step3 ? 'active' : '') }}">
            <div class="step-circle">@if($step4)✓@else 3 @endif</div>
            <span>Admin<br>review</span>
        </div>
        <div class="step-item {{ $step4 ? 'done active' : '' }}">
            <div class="step-circle">@if($step4)✓@else 4 @endif</div>
            <span>Account<br>active</span>
        </div>
    </div>
</div>

@if($isActive)
    <div class="card onboarding-complete">
        <div class="onboarding-complete-icon">
            <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2>You're all set!</h2>
        <p>Your account is verified. You can now place due diligence orders.</p>
        <a href="{{ route('client.dashboard') }}" class="btn btn-primary btn-lg">Go to dashboard</a>
    </div>
@else
    <div class="status-banner">
        <div>
            <div class="status-banner-label">Verification status</div>
            <span class="badge badge-{{ $status }}">{{ str_replace('_', ' ', $status) }}</span>
        </div>
        @if($status === 'kyc_submitted')
            <p style="font-size:0.85rem;color:var(--muted);max-width:280px;text-align:right;">
                Our team is reviewing your documents. Usually within 1–2 business days.
            </p>
        @endif
    </div>

    @if($status === 'rejected')
        <div class="alert alert-error" style="margin-bottom:1.25rem;">
            <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong>Application rejected</strong><br>
                {{ $company->rejection_reason }}
            </div>
        </div>
    @endif

    @if($canReopen ?? false)
        <div class="card" style="margin-bottom:1.25rem;padding:1rem 1.25rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
            <div>
                <strong>Need to change your documents?</strong>
                <p style="margin:0.25rem 0 0;font-size:0.875rem;color:var(--muted);">Go back to the upload step, update files, and submit again.</p>
            </div>
            <form method="POST" action="{{ route('client.onboarding.reopen') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Edit documents</button>
            </form>
        </div>
    @endif

    @if($status !== 'kyc_submitted' || $status === 'rejected')
    <div class="card kyc-upload-card">
        <h3>Upload KYC / AML documents</h3>
        <p class="onboarding-upload-intro">Add your files below. You can upload <strong>multiple files</strong> at once. PDF, JPG, PNG or ZIP — max <strong>{{ $maxMb }} MB</strong> per file.</p>

        <div class="file-dropzone kyc-single-dropzone" data-dropzone>
            <input type="file" id="kyc_document" accept=".pdf,.jpg,.jpeg,.png,.zip" multiple>
            <div class="file-dropzone-icon">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </div>
            <p class="file-dropzone-hint"><strong>Drag &amp; drop</strong> or click to choose files</p>
            <div class="file-name" data-file-name></div>
        </div>

        <div class="kyc-upload-hints">
            <p><strong>Government ID:</strong> Passport, Driving Licence, or National ID</p>
            <p><strong>Company Registration Documents:</strong> Certificate of Incorporation, Business Registration Certificate, Trade Licence, Tax Registration, Articles of Association, and similar company documents.</p>
            <p class="kyc-upload-hints-note">You may upload Government ID and Company Registration files together. At least one file is required before submit.</p>
        </div>

        @if($documents->isNotEmpty())
            <div class="kyc-uploaded-summary">
                <h4 class="kyc-section-title">Uploaded files</h4>
                <ul class="kyc-uploaded-list">
                    @foreach($documents as $doc)
                        <li class="kyc-uploaded-row">
                            <div class="kyc-uploaded-row-main">
                                <span>{{ $doc->original_name }}</span>
                                <span class="cell-muted">· {{ $doc->created_at->format('d M Y') }}</span>
                            </div>
                            @include('partials.kyc-document-actions', ['doc' => $doc])
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="onboarding-submit-block">
            <form method="POST" action="{{ route('client.onboarding.submit') }}" id="kyc-submit-form">
                @csrf
                <div class="onboarding-submit-actions onboarding-submit-actions--end">
                    <button type="submit" class="btn btn-primary btn-lg" id="kyc-submit-btn">
                        Submit for review
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @elseif($status === 'kyc_submitted')
    <div class="card" style="text-align:center;padding:2.5rem;">
        <div style="width:56px;height:56px;margin:0 auto 1rem;border-radius:50%;background:var(--warning-bg);display:flex;align-items:center;justify-content:center;color:var(--warning);">
            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 style="margin-bottom:0.5rem;">Under review</h3>
        <p style="color:var(--muted);font-size:0.9rem;margin-bottom:1.25rem;">Your documents have been submitted. We'll email you when your account is activated.</p>
        @if($canReopen ?? false)
            <form method="POST" action="{{ route('client.onboarding.reopen') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Back to upload documents</button>
            </form>
        @endif
    </div>
    @endif
@endif

<script>
    window.kycUploadConfig = {
        uploadUrl: @json(route('client.onboarding.store')),
        submitUrl: @json(route('client.onboarding.submit')),
        accountUrl: @json(route('client.onboarding.account')),
        token: @json(csrf_token()),
        maxBytes: {{ \App\Services\PublicUploadService::MAX_BYTES }},
        maxMb: {{ $maxMb }},
        hasAnyDocument: @json($canSubmit),
    };
</script>
<script src="{{ asset('js/kyc-upload.js') }}"></script>
@endsection
