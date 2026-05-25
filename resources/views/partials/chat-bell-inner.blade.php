<div class="chat-inbox-wrap" id="chat-inbox-bell"
    data-index-url="{{ route('chat.inbox.index') }}"
    data-read-all-url="{{ route('chat.inbox.read-all') }}"
    data-csrf="{{ csrf_token() }}"
    data-user-id="{{ auth()->id() }}">
    <button type="button" class="portal-icon-btn chat-inbox-btn" id="chat-inbox-toggle" aria-label="Chat messages" aria-expanded="false" aria-haspopup="true">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        <span class="notification-badge chat-inbox-badge" id="chat-inbox-badge" hidden>0</span>
    </button>
    <div class="notification-dropdown chat-inbox-dropdown" id="chat-inbox-dropdown" hidden>
        <div class="notification-dropdown-head">
            <strong>Messages</strong>
            <button type="button" class="notification-mark-all" id="chat-inbox-mark-all">Mark all read</button>
        </div>
        <ul class="notification-list chat-inbox-list" id="chat-inbox-list">
            <li class="notification-empty chat-inbox-loading">Loading messages…</li>
        </ul>
    </div>
</div>
