@extends('layouts.portal')
@section('title', 'Clients')
@section('container_class', 'page-container-wide')

@section('content')
        <header class="listing-hero">
            <div class="listing-hero-text">
                <h1>Clients</h1>
                <p>Manage client portal users — remove access or delete accounts individually.</p>
            </div>
        </header>

        <div class="stats-grid" style="margin-bottom:1.5rem;">
            <div class="stat-card stat-card-accent">
                <div class="label">Total companies</div>
                <div class="value">{{ $stats['client_companies'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Active</div>
                <div class="value">{{ $stats['active_clients'] }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Client users</div>
                <div class="value">{{ $stats['client_users'] }}</div>
            </div>
        </div>

        <div class="listing-panel">
            @include('partials.listing-toolbar', [
                'action' => route('admin.clients.index'),
                'placeholder' => 'Search name, email, phone or company…',
                'preserve' => ['q', 'company'],
                'filters' => [
                    [
                        'name' => 'company',
                        'label' => 'All companies',
                        'options' => $companyOptions,
                    ],
                ],
            ])

            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Company</th>
                            <th>Company status</th>
                            <th>Account</th>
                            <th>Registered</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($clientUsers as $clientUser)
                        @php
                            $company = $clientUser->company;
                            $canManage = in_array($clientUser->id, $manageableUserIds, true);
                            $onboardingReview = $company && in_array($company->status->value, ['pending', 'kyc_submitted'], true);
                        @endphp
                        <tr class="data-table-row">
                            <td>
                                <div class="analyst-cell">
                                    <span class="analyst-avatar">{{ strtoupper(substr($clientUser->name, 0, 2)) }}</span>
                                    <span>
                                        <strong>{{ $clientUser->name }}</strong>
                                        @if($clientUser->is_primary)
                                            <span class="pill pill-muted" style="margin-left:0.35rem;">Primary</span>
                                        @endif
                                        <span class="cell-sub">{{ $clientUser->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($company)
                                    <strong>{{ $company->name }}</strong>
                                @else
                                    <span class="cell-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($company)
                                    <span class="badge badge-{{ $company->status->value }}">{{ str_replace('_', ' ', $company->status->value) }}</span>
                                @else
                                    <span class="cell-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($clientUser->is_active && $company && $company->status !== \App\Enums\CompanyStatus::Suspended)
                                    <span class="badge badge-active">Active</span>
                                @else
                                    <span class="badge badge-inactive">No access</span>
                                @endif
                            </td>
                            <td><span class="cell-date">{{ $clientUser->created_at->format('d M Y') }}</span></td>
                            <td class="cell-action">
                                @if($onboardingReview && $company)
                                    <a href="{{ route('admin.onboarding.show', $company) }}" class="btn btn-secondary btn-sm">Review</a>
                                @else
                                    @include('partials.user-account-actions', [
                                        'user' => $clientUser,
                                        'canManage' => $canManage,
                                        'deactivateRoute' => route('admin.users.deactivate', $clientUser),
                                        'activateRoute' => route('admin.users.activate', $clientUser),
                                        'deleteRoute' => route('admin.users.destroy', $clientUser),
                                        'companySuspended' => $company && $company->status === \App\Enums\CompanyStatus::Suspended,
                                    ])
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    @if($hasFilters)
                                        <h3>No results</h3>
                                        <p>No client users match your search or filters.</p>
                                        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>
                                    @else
                                        <h3>No clients yet</h3>
                                        <p>Client users appear here after registration.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $clientUsers->links() }}
        </div>
@endsection
