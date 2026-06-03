@extends('layouts.portal')
@section('title', $company->name.' — Access')
@section('container_class', 'page-container-wide')

@section('content')
        <a href="{{ route('admin.clients.index') }}" class="back-link" style="margin-bottom:1rem;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to clients
        </a>
        <header class="listing-hero">
            <div class="listing-hero-text">
                <h1>{{ $company->name }}</h1>
                <p>Manage portal access for this company and its users.</p>
            </div>
            <div class="listing-hero-actions">
                <span class="badge badge-{{ $company->status->value }}">{{ str_replace('_', ' ', $company->status->value) }}</span>
            </div>
        </header>

        <div class="card" style="margin-bottom:1.5rem;">
            <div class="team-panel-head">
                <h2>Company access</h2>
            </div>
            <p class="form-field-hint" style="margin:0 0 1rem;">
                Suspending a company signs out all users and blocks new logins until access is restored.
            </p>
            @if($company->status === \App\Enums\CompanyStatus::Active)
                <form method="POST" action="{{ route('admin.clients.deactivate', $company) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Suspend company access</button>
                </form>
            @elseif($company->status === \App\Enums\CompanyStatus::Suspended)
                <form method="POST" action="{{ route('admin.clients.activate', $company) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Restore company access</button>
                </form>
            @else
                <p class="cell-muted">Company is {{ str_replace('_', ' ', $company->status->value) }}. Suspend is only available for active companies.</p>
            @endif
        </div>

        <div class="listing-panel">
            <div class="team-panel-head">
                <h2>Portal users</h2>
                <span class="pill pill-package">{{ $company->users->count() }}</span>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Account</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($company->users as $clientUser)
                        @php $canManage = in_array($clientUser->id, $manageableUserIds, true); @endphp
                        <tr>
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
                            <td><span class="cell-muted">Client</span></td>
                            <td>
                                @if($clientUser->is_active && $company->status !== \App\Enums\CompanyStatus::Suspended)
                                    <span class="badge badge-active">Active</span>
                                @else
                                    <span class="badge badge-inactive">No access</span>
                                @endif
                            </td>
                            <td class="cell-action">
                                @include('partials.user-account-actions', [
                                    'user' => $clientUser,
                                    'canManage' => $canManage,
                                    'deactivateRoute' => route('admin.users.deactivate', $clientUser),
                                    'activateRoute' => route('admin.users.activate', $clientUser),
                                    'deleteRoute' => route('admin.users.destroy', $clientUser),
                                    'companySuspended' => $company->status === \App\Enums\CompanyStatus::Suspended,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <h3>No users</h3>
                                    <p>This company has no client portal accounts yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
@endsection
