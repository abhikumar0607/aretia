@php
    $backRoute = $backRoute ?? null;
    $backLabel = $backLabel ?? 'Back to orders';
    $caseRoute = $caseRoute ?? null;
@endphp

@if($backRoute)
    <a href="{{ $backRoute }}" class="back-link">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $backLabel }}
    </a>
@endif

<header class="detail-hero">
    <div class="detail-hero-main">
        <span class="detail-eyebrow">Order</span>
        <h1>{{ $order->reference }}</h1>
        <p class="detail-subtitle">
            @if(isset($showCompany) && $showCompany)
                {{ $order->company->name }} &middot;
            @endif
            {{ $order->package->name }}
        </p>
        <div class="detail-badges">
            <span class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span>
            @if($order->subject_type)
                <span class="pill pill-muted">{{ ucfirst($order->subject_type->value) }}</span>
            @endif
        </div>
    </div>
    <div class="detail-hero-actions">
        @if($order->caseFile && !empty($enableCaseChat))
            <button type="button" class="btn btn-primary btn-sm case-chat-trigger" id="case-chat-toggle" aria-expanded="false" aria-controls="case-chat-widget">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                {{ $caseChatLabel ?? 'Chat with analyst' }}
            </button>
        @endif
        @if($order->caseFile && $caseRoute)
            <a href="{{ $caseRoute }}" class="btn btn-secondary btn-sm">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                Open case
            </a>
        @endif
    </div>
</header>

<div class="detail-meta-grid">
    @if(isset($showCompany) && $showCompany)
        <div class="detail-meta-card">
            <div class="detail-meta-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <span class="detail-meta-label">Company</span>
                <span class="detail-meta-value">{{ $order->company->name }}</span>
            </div>
        </div>
    @endif
    <div class="detail-meta-card">
        <div class="detail-meta-icon">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Placed by</span>
            <span class="detail-meta-value">{{ $order->user->name }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div class="detail-meta-icon detail-meta-icon-warn">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Due date</span>
            <span class="detail-meta-value">{{ $order->due_date?->format('d M Y') ?? 'Not set' }}</span>
        </div>
    </div>
    <div class="detail-meta-card">
        <div class="detail-meta-icon detail-meta-icon-success">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <span class="detail-meta-label">Confirmed</span>
            <span class="detail-meta-value">{{ $order->confirmed_at?->format('d M Y H:i') ?? '—' }}</span>
        </div>
    </div>
</div>

<div class="detail-content-grid">
    <section class="detail-section card">
        <div class="detail-section-head">
            <h3>
                @if($order->custom_request)
                    Custom request
                @else
                    Subject details
                @endif
            </h3>
            <span class="pill pill-package">{{ $order->package->name }}</span>
        </div>
        @if($order->custom_request)
            <div class="detail-prose">{{ $order->custom_request }}</div>
        @else
            <dl class="detail-dl">
                <div class="detail-dl-row">
                    <dt>Subject name</dt>
                    <dd>{{ $order->subject_name ?? '—' }}</dd>
                </div>
                <div class="detail-dl-row">
                    <dt>Type</dt>
                    <dd>{{ $order->subject_type ? ucfirst($order->subject_type->value) : '—' }}</dd>
                </div>
                @if($order->subject_details)
                    <div class="detail-dl-row">
                        <dt>Additional details</dt>
                        <dd class="detail-prose">{{ $order->subject_details }}</dd>
                    </div>
                @endif
            </dl>
        @endif
    </section>

    <aside class="detail-sidebar">
        @if($order->caseFile)
            <div class="detail-side-card">
                <h4>Linked case</h4>
                <p class="detail-side-ref">{{ $order->caseFile->reference }}</p>
                @if($order->caseFile->stage)
                    <span class="stage-pill" style="--stage-color: {{ $order->caseFile->stage->color }}">{{ $order->caseFile->stage->name }}</span>
                @endif
                @if($order->caseFile->assignee)
                    <div class="analyst-cell" style="margin-top:0.75rem;">
                        <span class="analyst-avatar">{{ strtoupper(substr($order->caseFile->assignee->name, 0, 2)) }}</span>
                        <span>{{ $order->caseFile->assignee->name }}</span>
                    </div>
                @else
                    <p class="detail-side-muted">Analyst not assigned yet</p>
                @endif
                @if(!empty($enableCaseChat))
                    <button type="button" class="btn btn-primary btn-sm case-chat-trigger" id="case-chat-toggle-sidebar" style="margin-top:1rem;width:100%;justify-content:center;" onclick="document.getElementById('case-chat-toggle')?.click()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Message analyst
                    </button>
                @endif
                @if($caseRoute)
                    <a href="{{ $caseRoute }}" class="btn btn-secondary btn-sm" style="margin-top:0.5rem;width:100%;justify-content:center;">View full case</a>
                @endif
            </div>
        @else
            <div class="detail-side-card detail-side-card-muted">
                <h4>Case</h4>
                <p class="detail-side-muted">No case file linked to this order yet.</p>
            </div>
        @endif

        @include('partials.order-due-date-form', ['order' => $order, 'dueDateAction' => $dueDateAction ?? null])
    </aside>
</div>

@if(!empty($documentUploadRoute) || $order->documents->count())
<section class="detail-section card" style="margin-top:1.25rem;">
    <div class="detail-section-head">
        <h3>Supporting documents</h3>
        <span class="pill pill-muted">{{ $order->documents->count() }} / 5 files</span>
    </div>

    @if($order->documents->count())
        <div class="detail-doc-list" style="margin-bottom:{{ !empty($documentUploadRoute) && $order->documents->count() < 5 ? '1.25rem' : '0' }};">
            @foreach($order->documents as $doc)
                <div class="detail-doc-item">
                    <span class="file-icon file-icon-pdf">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <div class="detail-doc-body">
                        <strong>{{ $doc->original_name }}</strong>
                        <span>{{ $doc->created_at->format('d M Y') }}</span>
                    </div>
                    @if(!empty($documentDownloadRoute))
                        <a href="{{ route($documentDownloadRoute, [$order, $doc]) }}" class="btn btn-secondary btn-sm">Download</a>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="form-field-hint" style="margin-bottom:1rem;">No documents yet. Upload supporting files below.</p>
    @endif

    @if(!empty($documentUploadRoute) && $order->documents->count() < 5)
        <form method="POST" action="{{ $documentUploadRoute }}" data-binary-upload>
            @csrf
            <p class="form-field-hint" style="margin-bottom:0.65rem;">PDF, Word, or images — max 5 MB per file.</p>
            <div class="import-file-zone order-file-zone" data-dropzone style="margin-bottom:0.75rem;">
                <input type="file" id="order_view_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <div class="import-file-zone-inner">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="import-file-label">Drop file or <strong>browse</strong></span>
                    <span class="import-file-name" data-file-name></span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Upload document</button>
        </form>
    @elseif(!empty($documentUploadRoute))
        <p class="form-field-hint" style="margin:0;">Maximum 5 documents reached.</p>
    @endif
</section>
@endif
