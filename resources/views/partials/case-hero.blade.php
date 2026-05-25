@php
    use App\Enums\UserRole;

    $stageColor = $case->stage?->color ?? '#6366f1';
    $viewer = auth()->user();
    $showClientContact = $viewer && (
        $viewer->hasRole(UserRole::Admin)
        || $viewer->hasRole(UserRole::SuperAdmin)
        || $viewer->hasRole(UserRole::Analyst)
    );
    $clientContact = $showClientContact ? $case->resolvedClient() : null;
@endphp

@if(!empty($backRoute))
    <a href="{{ $backRoute }}" class="back-link">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $backLabel ?? 'Back to cases' }}
    </a>
@endif

<header class="detail-hero case-hero">
    <div class="detail-hero-main">
        <span class="detail-eyebrow">Case</span>
        <h1>{{ $case->reference }}</h1>
        <p class="detail-subtitle">
            {{ $case->company->name }}
            @if($clientContact)
                &middot; Client: <strong>{{ $clientContact->name }}</strong>
            @endif
            &middot; {{ $case->order->package->name }}
        </p>
        <div class="detail-badges">
            @if($case->stage)
                <span class="stage-pill" style="--stage-color: {{ $stageColor }}">{{ $case->stage->name }}</span>
            @endif
            @if($clientContact)
                <span class="pill pill-client">Client: {{ $clientContact->name }}</span>
            @endif
            @if($case->assignee)
                @if($case->relationLoaded('analysts') && $case->analysts->count() > 1)
                    <span class="pill pill-muted" title="Lead analyst">Team: {{ $case->analystTeamNames() }}</span>
                    <span class="pill pill-muted">Lead: {{ $case->assignee->name }}</span>
                @else
                    <span class="pill pill-muted">Analyst: {{ $case->assignee->name }}</span>
                @endif
            @else
                <span class="pill pill-muted">Unassigned</span>
            @endif
        </div>
    </div>
    @if(!empty($enableChat) || !empty($heroAction))
    <div class="detail-hero-actions">
        @if(!empty($enableChat))
            <button type="button" class="btn btn-primary btn-sm case-chat-trigger" id="case-chat-toggle" aria-expanded="false" aria-controls="case-chat-widget">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                {{ $chatLabel ?? 'Open chat' }}
            </button>
        @endif
        @if(!empty($heroAction))
            {!! $heroAction !!}
        @endif
    </div>
    @endif
</header>

<div class="case-meta-grid">
    @if($clientContact)
    <div class="detail-meta-card detail-meta-card-client">
        <div class="detail-meta-icon detail-meta-icon-client">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Client contact</span>
            <span class="detail-meta-value">{{ $clientContact->name }}</span>
            @if($clientContact->email)
                <span class="detail-meta-sub">{{ $clientContact->email }}</span>
            @endif
            @if($clientContact->phone)
                <span class="detail-meta-sub">{{ $clientContact->phone }}</span>
            @endif
        </div>
    </div>
    @endif
    <div class="detail-meta-card">
        <div class="detail-meta-icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Company</span>
            <span class="detail-meta-value">{{ $case->company->name }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div class="detail-meta-icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Order</span>
            <span class="detail-meta-value">{{ $case->order->reference }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div class="detail-meta-icon detail-meta-icon-warn">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Due date</span>
            <span class="detail-meta-value">{{ $case->order->due_date?->format('d M Y') ?? 'TBD' }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div class="detail-meta-icon detail-meta-icon-success">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Subject</span>
            <span class="detail-meta-value">{{ $case->order->subject_name ?? 'Custom' }}</span>
        </div>
    </div>
</div>
