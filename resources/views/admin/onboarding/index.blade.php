@extends('layouts.portal')
@section('title', 'Onboarding Approvals')

@section('content')
<div class="page-header">
    <h1>Onboarding approvals</h1>
    <p>Review client KYC documents and activate accounts.</p>
</div>

@php
    $pendingCount = $companies->total();
@endphp

<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card stat-card-warn">
        <div class="label">Awaiting review</div>
        <div class="value">{{ $pendingCount }}</div>
    </div>
</div>

<div class="listing-panel">
    <div class="card-flush" style="border:none;box-shadow:none;border-radius:0;">
    @forelse($companies as $company)
        <div class="data-row">
            <div class="data-row-avatar">{{ strtoupper(substr($company->name, 0, 2)) }}</div>
            <div class="data-row-body">
                <strong>{{ $company->name }}</strong>
                <span>{{ $company->email }}</span>
            </div>
            <div class="data-row-meta">
                <span class="badge badge-{{ $company->status->value }}">{{ str_replace('_', ' ', $company->status->value) }}</span>
                <span class="data-row-date">{{ $company->updated_at->format('d M Y') }}</span>
            </div>
            <div class="data-row-action">
                <a href="{{ route('admin.onboarding.show', $company) }}" class="btn btn-primary btn-sm">
                    Review
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="empty-state-icon">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <h3>All caught up</h3>
            <p>No pending onboarding requests right now.</p>
        </div>
    @endforelse
    </div>
    {{ $companies->links() }}
</div>
@endsection
