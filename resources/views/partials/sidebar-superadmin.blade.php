@php
    $user = auth()->user();
    $role = $user->role;
    $initials = $user->initials();
    $icon = fn ($d) => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="'.$d.'"/></svg>';
@endphp

<aside class="sidebar sidebar--superadmin">

    @include('partials.brand-logo', [
        'link' => route('superadmin.dashboard'),
        'linkClass' => 'sidebar-brand',
        'class' => 'site-logo-sidebar',
        'width' => 168,
        'height' => 44,
    ])

    <div class="sidebar-user">
        @if($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" alt="" class="sidebar-avatar-img avatar-super">
        @else
            <div class="sidebar-avatar avatar-super">{{ $initials }}</div>
        @endif
        <div class="name">{{ $user->name }}</div>
        <div class="company">{{ $role->label() }}</div>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <div class="sidebar-nav-label">Main</div>
        <a href="{{ route('superadmin.dashboard') }}" class="{{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">{!! $icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!} Dashboard</a>
        @perm('onboarding.approve')
        <a href="{{ route('superadmin.onboarding.index') }}" class="{{ request()->routeIs('superadmin.onboarding.*') ? 'active' : '' }}">{!! $icon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z') !!} Onboarding</a>
        @endperm
        @if($user->hasPermission('orders.create') || $user->hasPermission('orders.approve'))
        <a href="{{ route('superadmin.orders.index') }}" class="{{ request()->routeIs('superadmin.orders.*') ? 'active' : '' }}">{!! $icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2') !!} Orders</a>
        @endif
        @perm('cases.manage')
        <a href="{{ route('superadmin.cases.index') }}" class="{{ request()->routeIs('superadmin.cases.*') ? 'active' : '' }}">{!! $icon('M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z') !!} Cases</a>
        @endperm
        @if($user->hasPermission('reports.view') || $user->hasPermission('reports.manage'))
        <a href="{{ route('superadmin.reports.index') }}" class="{{ request()->routeIs('superadmin.reports.*') ? 'active' : '' }}">{!! $icon('M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z') !!} Reports</a>
        @endif
        @perm('workflow.manage')
        <a href="{{ route('superadmin.workflow.index') }}" class="{{ request()->routeIs('superadmin.workflow.*') ? 'active' : '' }}">{!! $icon('M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15') !!} Workflow</a>
        @endperm
        @perm('audit.view')
        <a href="{{ route('superadmin.audit.index') }}" class="{{ request()->routeIs('superadmin.audit.*') ? 'active' : '' }}">{!! $icon('M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z') !!} Audit Trail</a>
        @endperm

        <div class="sidebar-nav-label">People</div>
        @if($user->hasPermission('clients.view') || $user->hasPermission('clients.manage'))
        <a href="{{ route('superadmin.clients.index') }}" class="{{ request()->routeIs('superadmin.clients.*') ? 'active' : '' }}">{!! $icon('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4') !!} Client</a>
        @endif
        @if($user->hasPermission('employees.view') || $user->hasPermission('employees.manage'))
        <a href="{{ route('superadmin.employees.index') }}" class="{{ request()->routeIs('superadmin.employees.*') ? 'active' : '' }}">{!! $icon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z') !!} Employees</a>
        @endif

        <div class="sidebar-nav-label">System</div>
        <a href="{{ route('superadmin.roles.index') }}" class="{{ request()->routeIs('superadmin.roles.*') ? 'active' : '' }}">{!! $icon('M12 11c0-1.657 1.343-3 3-3s3 1.343 3 3v2m-6 0h6m-9 8h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z') !!} Permissions &amp; Roles</a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary" style="width:100%;">Logout</button>
        </form>
    </div>
</aside>
