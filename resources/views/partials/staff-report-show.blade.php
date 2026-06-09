@extends('layouts.portal')
@section('title', $report->title)
@section('container_class', 'page-container-wide')

@section('content')
@if(in_array($routePrefix, ['analyst', 'qa', 'fqa'], true))
<a href="{{ \App\Support\PortalRoute::route('cases.show', $report->caseFile) }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to case
</a>
@else
<a href="{{ route($routePrefix.'.reports.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    All reports
</a>
@endif

<header class="detail-hero">
    <div class="detail-hero-main">
        <span class="detail-eyebrow">Report</span>
        <h1>{{ $report->title }}</h1>
        <p class="detail-subtitle">{{ $report->original_name }}</p>
        <div class="detail-badges">
            @if($report->is_password_protected)
                <span class="pill pill-muted">Password protected for client</span>
            @endif
        </div>
    </div>
    <div class="detail-hero-actions">
        <a href="{{ route($routePrefix.'.reports.download', $report) }}" class="btn btn-primary btn-sm">Download report</a>
    </div>
</header>

<div class="detail-meta-grid">
    <div class="detail-meta-card">
        <div>
            <span class="detail-meta-label">Case</span>
            <span class="detail-meta-value">
                <a href="{{ route($routePrefix.'.cases.show', $report->caseFile) }}" class="row-link">{{ $report->caseFile->reference }}</a>
            </span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div>
            <span class="detail-meta-label">Company</span>
            <span class="detail-meta-value">{{ $report->caseFile->company->name }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div>
            <span class="detail-meta-label">Subject</span>
            <span class="detail-meta-value">{{ $report->caseFile->order->subject_name ?? 'Custom' }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div>
            <span class="detail-meta-label">Submitted by</span>
            <span class="detail-meta-value">{{ $report->uploader?->displayNameWithRole() ?? '—' }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div>
            <span class="detail-meta-label">Delivered</span>
            <span class="detail-meta-value">{{ $report->delivered_at->format('d M Y, H:i') }}</span>
        </div>
    </div>
    @if($report->downloaded_at)
        <div class="detail-meta-card">
            <div>
                <span class="detail-meta-label">Client downloaded</span>
                <span class="detail-meta-value">{{ $report->downloaded_at->format('d M Y, H:i') }}</span>
            </div>
        </div>
    @endif
</div>
@endsection
