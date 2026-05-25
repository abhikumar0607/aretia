<div class="notification-bell-wrap" id="notification-bell"
    data-index-url="{{ route('notifications.index') }}"
    data-read-all-url="{{ route('notifications.read-all') }}"
    data-read-url-template="{{ route('notifications.read', ['id' => '__ID__']) }}"
    data-csrf="{{ csrf_token() }}">
    <button type="button" class="portal-icon-btn notification-bell-btn" id="notification-bell-toggle" aria-label="Notifications" aria-expanded="false" aria-haspopup="true">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="notification-badge" id="notification-badge" hidden>0</span>
    </button>
    <div class="notification-dropdown" id="notification-dropdown" hidden>
        <div class="notification-dropdown-head">
            <strong>Notifications</strong>
            <button type="button" class="notification-mark-all" id="notification-mark-all">Mark all read</button>
        </div>
        <ul class="notification-list" id="notification-list">
            <li class="notification-empty">Loading…</li>
        </ul>
    </div>
</div>
