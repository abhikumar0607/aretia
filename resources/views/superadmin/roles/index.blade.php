@extends('layouts.portal')
@section('title', 'Permissions & Roles')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Permissions &amp; Roles</h1>
        <p>Manage role-based access and granular permissions across the portal.</p>
    </div>
</header>

<div class="listing-panel">
    <div class="empty-state">
        <div class="empty-state-icon" aria-hidden="true">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c0-1.657 1.343-3 3-3s3 1.343 3 3v2m-6 0h6m-9 8h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3>Coming soon</h3>
        <p>This section will let Super Admins define custom roles, scope permissions per module, and assign access policies.</p>
        <p class="empty-state-sub">It is visible only to Super Admin and is reserved for future configuration.</p>
    </div>
</div>
@endsection
