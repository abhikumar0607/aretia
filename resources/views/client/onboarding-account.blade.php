@extends('layouts.portal')
@section('title', 'Your account details')

@php
    $status = $company?->status?->value ?? 'pending';
@endphp

@section('content')

<a href="{{ route('client.onboarding') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to document upload
</a>

<div class="onboarding-hero">
    <h1>Your account details</h1>
    <p>Update your contact and company information before submitting KYC documents.</p>

    <div class="steps-track">
        <div class="step-item done active">
            <div class="step-circle">✓</div>
            <span>Account<br>created</span>
        </div>
        <div class="step-item">
            <div class="step-circle">2</div>
            <span>Upload<br>KYC docs</span>
        </div>
        <div class="step-item">
            <div class="step-circle">3</div>
            <span>Admin<br>review</span>
        </div>
        <div class="step-item">
            <div class="step-circle">4</div>
            <span>Account<br>active</span>
        </div>
    </div>
</div>

<div class="card onboarding-account-card">
    <h3>Account created</h3>
    <p class="onboarding-upload-intro">These details were provided at registration. Update them if anything has changed.</p>

    <form method="POST" action="{{ route('client.onboarding.account.update') }}" class="onboarding-account-form">
        @csrf
        @method('PUT')

        <div class="form-grid onboarding-account-grid">
            <div class="form-group">
                <label for="account_name">Full name</label>
                <input type="text" id="account_name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
            </div>
            <div class="form-group">
                <label for="account_company">Company name</label>
                <input type="text" id="account_company" name="company" value="{{ old('company', $company->name) }}" required autocomplete="organization">
            </div>
            <div class="form-group">
                <label for="account_email">Work email</label>
                <input type="email" id="account_email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="account_phone">Phone</label>
                <input type="text" id="account_phone" name="phone" value="{{ old('phone', $user->phone) }}" required autocomplete="tel">
            </div>
        </div>

        <div class="onboarding-submit-actions">
            <a href="{{ route('client.onboarding') }}" class="btn btn-secondary btn-lg">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg">
                Save &amp; continue to KYC
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </form>
</div>

@endsection
