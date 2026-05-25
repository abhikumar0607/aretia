@php
    $chatUser = auth()->user();
    $chatPartner = $case->chatPartnerFor($chatUser);
    $isAnalystSide = $chatUser->role->value === 'analyst' || in_array($chatUser->role->value, ['admin', 'superadmin'], true);

    $chatTitle = $chatPartner?->name ?? ($isAnalystSide ? 'Client user' : 'Analyst');
    $chatSubtitle = $isAnalystSide
        ? trim(($case->company->name ?? 'Company').($chatPartner?->email ? ' · '.$chatPartner->email : ''))
        : ($chatPartner ? 'Assigned analyst' : 'Analyst not assigned yet');
@endphp
<div id="case-chat-root"
    class="case-chat-root"
    data-case-id="{{ $case->id }}"
    data-case-ref="{{ $case->reference }}"
    data-partner-id="{{ $chatPartner?->id }}"
    data-partner-name="{{ $chatPartner?->name }}"
    data-my-name="{{ $chatUser->name }}"
    data-index-url="{{ route('cases.messages.index', $case) }}"
    data-store-url="{{ route('cases.messages.store', $case) }}"
    data-read-url="{{ route('cases.messages.read', $case) }}"
    data-current-user-id="{{ $chatUser->id }}"
    data-csrf="{{ csrf_token() }}">
    <div id="case-chat-widget" class="case-chat-widget" hidden aria-hidden="true">
        <div class="case-chat-widget-head">
            <div class="case-chat-widget-title">
                <span class="case-chat-with-label">Chat with</span>
                <strong class="case-chat-partner-name">{{ $chatTitle }}</strong>
                <span class="case-chat-partner-meta">{{ $chatSubtitle }}</span>
                <span class="case-chat-case-ref">Case: {{ $case->reference }}</span>
            </div>
            <button type="button" class="case-chat-close" id="case-chat-close" aria-label="Close chat">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="case-chat-messages" id="case-chat-messages">
            <p class="case-chat-loading">Loading messages…</p>
        </div>
        <form id="case-chat-form" class="case-chat-form">
            <textarea name="body" id="case-chat-input" rows="2" placeholder="Type a message…" required maxlength="5000"></textarea>
            <button type="submit" class="btn btn-primary btn-sm case-chat-send">Send</button>
        </form>
    </div>
</div>
