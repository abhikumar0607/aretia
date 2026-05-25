@php
    $clientsActive = request()->routeIs('admin.clients.*');
    $analystsActive = request()->routeIs('admin.analysts.*');
@endphp
<nav class="admin-people-nav" aria-label="Clients and analysts">
    <a href="{{ route('admin.clients.index') }}" class="admin-people-nav-item {{ $clientsActive ? 'is-active' : '' }}">
        <span class="admin-people-nav-icon" aria-hidden="true">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </span>
        <span class="admin-people-nav-text">
            <strong>Clients</strong>
            <small>Companies &amp; contacts</small>
        </span>
    </a>
    <a href="{{ route('admin.analysts.index') }}" class="admin-people-nav-item {{ $analystsActive ? 'is-active' : '' }}">
        <span class="admin-people-nav-icon" aria-hidden="true">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </span>
        <span class="admin-people-nav-text">
            <strong>Analysts</strong>
            <small>Team &amp; assignments</small>
        </span>
    </a>
</nav>
