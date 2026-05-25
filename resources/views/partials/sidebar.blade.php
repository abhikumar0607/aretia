@php
    $user = auth()->user();
    $role = $user->role;
    $isSuperAdmin = $role->value === 'superadmin';
    $isAdmin = $role->value === 'admin';
    $isStaff = $isSuperAdmin || $isAdmin;
    $homeRoute = $role->dashboardRoute();
    $initials = $user->initials();
    $icon = fn ($d) => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="'.$d.'"/></svg>';
@endphp
<aside class="sidebar">
    @include('partials.brand-logo', [
        'link' => route($homeRoute),
        'linkClass' => 'sidebar-brand',
        'class' => 'site-logo-sidebar',
        'width' => 168,
        'height' => 44,
    ])
    <div class="sidebar-user">
        @if($user->avatarUrl())
            <img src="{{ $user->avatarUrl() }}" alt="" class="sidebar-avatar-img {{ $isSuperAdmin ? 'avatar-super' : '' }}">
        @else
            <div class="sidebar-avatar {{ $isSuperAdmin ? 'avatar-super' : '' }}">{{ $initials }}</div>
        @endif
        <div class="name">{{ $user->name }}</div>
        @if($user->company)
            <div class="company">{{ $user->company->name }}</div>
        @endif
        <span class="role {{ $isSuperAdmin ? 'role-super' : '' }}">{{ $role->label() }}</span>
    </div>
    <nav class="sidebar-nav">
        @if($isStaff)
            <div class="sidebar-nav-label">{{ $isSuperAdmin ? 'Super Admin' : 'Admin' }}</div>
            <a href="{{ route($homeRoute) }}" class="{{ request()->routeIs($homeRoute) ? 'active' : '' }}">{!! $icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!} Dashboard</a>
            <a href="{{ route('admin.clients.index') }}" class="{{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">{!! $icon('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4') !!} Clients</a>
            <a href="{{ route('admin.analysts.index') }}" class="{{ request()->routeIs('admin.analysts.*') ? 'active' : '' }}">{!! $icon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z') !!} Analysts</a>
            <a href="{{ route('admin.onboarding.index') }}" class="{{ request()->routeIs('admin.onboarding.*') ? 'active' : '' }}">{!! $icon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z') !!} Onboarding</a>
            <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">{!! $icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2') !!} Orders</a>
            <a href="{{ route('admin.cases.index') }}" class="{{ request()->routeIs('admin.cases.*') ? 'active' : '' }}">{!! $icon('M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z') !!} Cases</a>
            <a href="{{ route('admin.workflow.index') }}" class="{{ request()->routeIs('admin.workflow.*') ? 'active' : '' }}">{!! $icon('M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15') !!} Workflow</a>
            <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">{!! $icon('M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z') !!} Audit Trail</a>
        @elseif($role->value === 'client')
            <div class="sidebar-nav-label">Portal</div>
            @if($user->isClientActive())
                <a href="{{ route('client.dashboard') }}" class="{{ request()->routeIs('client.dashboard') ? 'active' : '' }}">{!! $icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!} Home</a>
                <a href="{{ route('client.orders.index') }}" class="{{ request()->routeIs('client.orders.*') ? 'active' : '' }}">{!! $icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2') !!} Orders</a>
                <a href="{{ route('client.cases.index') }}" class="{{ request()->routeIs('client.cases.*') ? 'active' : '' }}">{!! $icon('M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z') !!} My Cases</a>
                <a href="{{ route('client.reports.index') }}" class="{{ request()->routeIs('client.reports.*') ? 'active' : '' }}">{!! $icon('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z') !!} Reports</a>
            @endif
            <a href="{{ route('client.onboarding') }}" class="{{ request()->routeIs('client.onboarding*') ? 'active' : '' }}">{!! $icon('M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z') !!} Onboarding</a>
        @elseif($role->value === 'analyst')
            <div class="sidebar-nav-label">Analyst</div>
            <a href="{{ route('analyst.dashboard') }}" class="{{ request()->routeIs('analyst.dashboard') ? 'active' : '' }}">{!! $icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6') !!} Dashboard</a>
            <a href="{{ route('analyst.cases.index') }}" class="{{ request()->routeIs('analyst.cases.*') ? 'active' : '' }}">{!! $icon('M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z') !!} My Cases</a>
        @endif
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary" style="width:100%;">Logout</button>
        </form>
    </div>
</aside>
