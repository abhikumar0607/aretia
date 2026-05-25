@extends('layouts.portal')
@section('title', 'Review — '.$company->name)

@section('content')
<a href="{{ route('admin.onboarding.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to queue
</a>

<div class="review-header card">
    <div class="review-header-main">
        <div class="review-avatar">{{ strtoupper(substr($company->name, 0, 2)) }}</div>
        <div>
            <h1>{{ $company->name }}</h1>
            <p>{{ $company->email }} &middot; {{ $company->phone ?? 'No phone' }}</p>
            @if($company->users->first())
                <p style="font-size:0.8rem;color:var(--muted);margin-top:0.25rem;">Contact: {{ $company->users->first()->name }}</p>
            @endif
        </div>
    </div>
    <span class="badge badge-{{ $company->status->value }}">{{ str_replace('_', ' ', $company->status->value) }}</span>
</div>

<div class="card">
    <h3>KYC / AML documents</h3>
    @if($company->kycDocuments->isEmpty())
        <p style="color:var(--muted);font-size:0.9rem;">No documents uploaded yet.</p>
    @else
        <div class="doc-list">
            @foreach($company->kycDocuments as $doc)
                <div class="doc-item">
                    <div class="doc-item-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="doc-item-body">
                        <strong>{{ $doc->type === 'national_id' ? 'Government ID' : 'Incorporation papers' }}</strong>
                        <span>{{ $doc->original_name }} &middot; by {{ $doc->uploader->name }}</span>
                    </div>
                    <a href="{{ route('admin.kyc.download', $doc) }}" class="btn btn-secondary btn-sm">Download</a>
                </div>
            @endforeach
        </div>
    @endif
</div>

@if(in_array($company->status->value, ['pending', 'kyc_submitted']))
<div class="card review-actions-card">
    <h3>Decision</h3>
    <div class="review-actions">
        <form method="POST" action="{{ route('admin.onboarding.approve', $company) }}" class="approve-form">
            @csrf
            <p class="form-hint" style="margin-bottom:1rem;">Approve to activate the client account and send email notification.</p>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Approve & activate account
            </button>
        </form>
        <form method="POST" action="{{ route('admin.onboarding.reject', $company) }}" class="reject-form">
            @csrf
            <label>Rejection reason</label>
            <textarea name="rejection_reason" placeholder="Explain why the application was rejected..." required></textarea>
            <button type="submit" class="btn btn-danger btn-lg" style="width:100%;margin-top:0.5rem;">Reject application</button>
        </form>
    </div>
</div>
@elseif($company->status->value === 'active')
<div class="alert alert-success">
    <svg class="alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>This company was approved on {{ $company->approved_at?->format('d M Y H:i') }}.</span>
</div>
@endif
@endsection
