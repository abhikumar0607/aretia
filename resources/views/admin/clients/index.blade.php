@extends('layouts.portal')
@section('title', 'Clients')
@section('container_class', 'page-container-wide')

@section('content')
<div class="admin-people-layout">
    @include('partials.admin-people-nav')

    <div class="admin-people-main">
        <header class="listing-hero">
            <div class="listing-hero-text">
                <h1>Clients</h1>
                <p>Registered client companies and primary contacts.</p>
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
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Primary contact</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($companies as $company)
                        @php $contact = $company->users->first(); @endphp
                        <tr class="data-table-row">
                            <td>
                                <div class="analyst-cell">
                                    <span class="analyst-avatar">{{ strtoupper(substr($company->name, 0, 2)) }}</span>
                                    <span>
                                        <strong>{{ $company->name }}</strong>
                                        <span class="cell-sub">{{ $company->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($contact)
                                    <span class="cell-client-name">{{ $contact->name }}</span>
                                    <span class="cell-sub">{{ $contact->email }}</span>
                                @else
                                    <span class="cell-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge badge-{{ $company->status->value }}">{{ str_replace('_', ' ', $company->status->value) }}</span></td>
                            <td><span class="cell-date">{{ $company->created_at->format('d M Y') }}</span></td>
                            <td class="cell-action">
                                @if(in_array($company->status->value, ['pending', 'kyc_submitted'], true))
                                    <a href="{{ route('admin.onboarding.show', $company) }}" class="btn btn-secondary btn-sm">Review</a>
                                @else
                                    <span class="cell-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <h3>No clients yet</h3>
                                    <p>Client companies appear here after registration.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $companies->links() }}
        </div>
    </div>
</div>
@endsection
