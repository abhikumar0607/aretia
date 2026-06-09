@php
    use App\Enums\UserRole;

    $stageColor = $case->visibleStageColor();
    $viewer = auth()->user();
    $clientContact = $viewer && (
        $viewer->hasRole(UserRole::Admin)
        || $viewer->hasRole(UserRole::SuperAdmin)
    ) ? $case->resolvedClient() : null;

    $clientNameOnly = $viewer && $viewer->isEmployee()
        ? $case->resolvedClient()
        : null;
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
        <h1 class="case-hero-ref">{{ $case->reference }}</h1>
        <p class="case-hero-subject">{{ $case->order->subject_name ?? 'Custom' }}</p>
        <p class="detail-subtitle">
            {{ $case->company->name }}
            @if($clientContact)
                &middot; Client: <strong>{{ $clientContact->name }}</strong>
            @elseif($clientNameOnly)
                &middot; Client: <strong>{{ $clientNameOnly->name }}</strong>
            @endif
            &middot; {{ $case->order->package->name }}
        </p>
        <div class="detail-badges">
            @if($case->stage || $viewer?->hasRole(\App\Enums\UserRole::Client))
                <span class="stage-pill" style="--stage-color: {{ $stageColor }}">{{ $case->visibleStageLabel() }}</span>
            @endif
            @if($clientContact)
                <span class="pill pill-client">Client: {{ $clientContact->name }}</span>
            @elseif($clientNameOnly)
                <span class="pill pill-client">Client: {{ $clientNameOnly->name }}</span>
            @endif
            @if($case->hasFullEmployeeTeam())
                @foreach(\App\Enums\EmployeeType::cases() as $type)
                    @php $members = $case->teamByEmployeeType()[$type->value] ?? collect(); @endphp
                    @foreach($members as $member)
                        <span class="pill {{ $member->role->badgeClass() }}">
                            {{ $type->label() }}: {{ $member->name }}
                        </span>
                    @endforeach
                @endforeach
            @elseif($case->assignee)
                <span class="pill pill-muted">Team incomplete</span>
                <span class="pill pill-muted">Lead: {{ $case->assignee->displayNameWithRole() }}</span>
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

<div class="card case-details-box">
    <div class="case-details-grid">
        @if($clientContact)
            <div class="case-detail-item">
                <span class="case-detail-label">Client contact</span>
                <span class="case-detail-value">{{ $clientContact->name }}</span>
                @if($clientContact->email)
                    <span class="case-detail-sub">{{ $clientContact->email }}</span>
                @endif
                @if($clientContact->phone)
                    <span class="case-detail-sub">{{ $clientContact->phone }}</span>
                @endif
            </div>
        @elseif($clientNameOnly)
            <div class="case-detail-item">
                <span class="case-detail-label">Client contact</span>
                <span class="case-detail-value">{{ $clientNameOnly->name }}</span>
                <span class="case-detail-sub">Contact details hidden</span>
            </div>
        @endif
        <div class="case-detail-item">
            <span class="case-detail-label">Company</span>
            <span class="case-detail-value">{{ $case->company->name }}</span>
        </div>
        <div class="case-detail-item">
            <span class="case-detail-label">Order</span>
            <span class="case-detail-value">{{ $case->order->reference }}</span>
        </div>
        <div class="case-detail-item">
            <span class="case-detail-label">Confirmed</span>
            <span class="case-detail-value">{{ $case->order->confirmed_at?->format('d M Y') ?? '—' }}</span>
            @if($case->order->confirmed_at)
                <span class="case-detail-sub">{{ $case->order->confirmed_at->format('H:i') }}</span>
            @endif
        </div>
        <div class="case-detail-item">
            <span class="case-detail-label">Due date</span>
            <span class="case-detail-value">{{ $case->portalDueDateLabel() }}</span>
        </div>
        @if(auth()->user()?->isEmployee() === false)
            @foreach(\App\Enums\EmployeeType::cases() as $employeeType)
                @php $roleDueDate = $case->teamDueDatesByRole()[$employeeType->value] ?? null; @endphp
                @if($roleDueDate)
                    <div class="case-detail-item">
                        <span class="case-detail-label">{{ $employeeType->label() }} due date</span>
                        <span class="case-detail-value">{{ \Illuminate\Support\Carbon::parse($roleDueDate)->format('d M Y') }}</span>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>
