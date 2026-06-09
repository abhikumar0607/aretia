@extends('layouts.portal')
@section('title', 'Employees')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Employees</h1>
        <p>Manage Analyst, QA, and FQA team accounts.</p>
    </div>
    <div class="listing-hero-actions">
        @perm('employees.manage')
        <a href="{{ route('superadmin.employees.create') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add employee
        </a>
        @endperm
    </div>
</header>

<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card stat-card-accent">
        <div class="label">Total employees</div>
        <div class="value">{{ $stats['employees'] }}</div>
    </div>
</div>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route('superadmin.employees.index'),
        'placeholder' => 'Search name, email or phone…',
        'preserve' => ['q', 'role'],
        'filters' => [
            [
                'name' => 'role',
                'label' => 'All roles',
                'options' => $roleOptions,
            ],
        ],
    ])

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Assigned cases</th>
                    <th>Account</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($employees as $employee)
                @php $canManage = in_array($employee->id, $manageableUserIds, true) && auth()->user()->hasPermission('employees.manage'); @endphp
                <tr>
                    <td>
                        <div class="analyst-cell">
                            <span class="analyst-avatar">{{ strtoupper(substr($employee->name, 0, 2)) }}</span>
                            <span>
                                <strong>{{ $employee->name }}</strong>
                                <span class="cell-sub">{{ $employee->email }}</span>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="badge {{ $employee->role->badgeClass() }}">{{ $employee->role->label() }}</span>
                    </td>
                    <td><span class="cell-muted">{{ $employee->phone ?? '—' }}</span></td>
                    <td><span class="pill pill-muted">{{ $employee->assigned_cases_count }} cases</span></td>
                    <td>
                        @if($employee->is_active)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-inactive">No access</span>
                        @endif
                    </td>
                    <td class="cell-action">
                        @include('partials.user-account-actions', [
                            'user' => $employee,
                            'canManage' => $canManage,
                            'editRoute' => route('superadmin.employees.edit', $employee),
                            'deactivateRoute' => route('superadmin.users.deactivate', $employee),
                            'activateRoute' => route('superadmin.users.activate', $employee),
                            'deleteRoute' => route('superadmin.users.destroy', $employee),
                        ])
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            @if($hasFilters)
                                <h3>No results</h3>
                                <p>No employees match your search or filters.</p>
                                <a href="{{ route('superadmin.employees.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>
                            @else
                                <h3>No employees yet</h3>
                                <p>Add your first team member to get started.</p>
                                <a href="{{ route('superadmin.employees.create') }}" class="btn btn-primary btn-sm">Add employee</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $employees->links() }}
</div>
@endsection

