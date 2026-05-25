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
@endphp

@section('content')

<div class="onboarding-hero">
    <h1>Complete your onboarding</h1>
    <p>Verify your identity and company documents to activate your Aretia account.</p>

    <div class="steps-track">
        <div class="step-item {{ $step1 ? 'done' : '' }}">
            <div class="step-circle">@if($step1)✓@else 1 @endif</div>
            <span>Account<br>created</span>
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

    @if($status !== 'kyc_submitted' || $status === 'rejected')
    <div class="card">
        <h3>Upload KYC / AML documents</h3>
        <p style="color:var(--muted);font-size:0.875rem;margin:-0.5rem 0 1.25rem;">PDF, JPG or PNG — <strong>max 5 MB</strong> per file (any smaller size is fine)</p>

        <form id="kyc-upload-form" method="POST" action="{{ route('client.onboarding.store') }}">
            @csrf
            <div class="upload-grid">
                <div class="file-dropzone" data-dropzone>
                    <input type="file" id="id_document" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="file-dropzone-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-5M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </div>
                    <h4>Government ID</h4>
                    <p>Passport, national ID card, or driving licence</p>
                    <div class="file-name" data-file-name></div>
                </div>
                <div class="file-dropzone" data-dropzone>
                    <input type="file" id="incorporation_document" accept=".pdf,.jpg,.jpeg,.png" required>
                    <div class="file-dropzone-icon">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <h4>Company incorporation</h4>
                    <p>Certificate of incorporation or registration papers</p>
                    <div class="file-name" data-file-name></div>
                </div>
            </div>

            <div class="form-actions">
                <p class="form-hint">By submitting, you confirm documents are <strong>authentic</strong> and accurate.</p>
                <button type="submit" class="btn btn-primary btn-lg">
                    Submit for review
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </form>
    </div>
    @elseif($status === 'kyc_submitted')
    <div class="card" style="text-align:center;padding:2.5rem;">
        <div style="width:56px;height:56px;margin:0 auto 1rem;border-radius:50%;background:var(--warning-bg);display:flex;align-items:center;justify-content:center;color:var(--warning);">
            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 style="margin-bottom:0.5rem;">Under review</h3>
        <p style="color:var(--muted);font-size:0.9rem;">Your documents have been submitted. We'll email you when your account is activated.</p>
    </div>
    @endif
@endif

@if($documents->count())
<div class="card">
    <h3>Submitted documents</h3>
    <div class="doc-list">
        @foreach($documents as $doc)
            <div class="doc-item">
                <div class="doc-item-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="doc-item-body">
                    <strong>{{ $doc->type === 'national_id' ? 'Government ID' : 'Incorporation papers' }}</strong>
                    <span>{{ $doc->original_name }}</span>
                </div>
                <span class="doc-item-date">{{ $doc->created_at->format('d M Y') }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

<script src="{{ asset('js/kyc-upload.js') }}"></script>
@endsection
