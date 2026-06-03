@extends('layouts.portal')
@section('title', 'Analysts')
@section('container_class', 'page-container-wide')

@section('content')
        <header class="listing-hero">
            <div class="listing-hero-text">
                <h1>Analysts</h1>
                <p>Create analyst logins and view assigned workload.</p>
            </div>
            <div class="listing-hero-actions">
                <a href="#add-analyst" class="btn btn-primary btn-sm">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add analyst
                </a>
            </div>
        </header>

        <div class="stats-grid" style="margin-bottom:1.5rem;">
            <div class="stat-card stat-card-accent">
                <div class="label">Total analysts</div>
                <div class="value">{{ $stats['analysts'] }}</div>
            </div>
        </div>

        <div class="admin-analysts-grid">
            <section class="listing-panel admin-analysts-list">
                <div class="team-panel-head">
                    <h2>Analyst accounts</h2>
                    <span class="pill pill-package">{{ $analysts->count() }}</span>
                </div>

                @include('partials.listing-toolbar', [
                    'action' => route('admin.analysts.index'),
                    'placeholder' => 'Search name, email or phone…',
                    'preserve' => ['q'],
                ])

                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Analyst</th>
                                <th>Phone</th>
                                <th>Assigned cases</th>
                                <th>Account</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($analysts as $analyst)
                            @php $canManage = in_array($analyst->id, $manageableUserIds, true); @endphp
                            <tr>
                                <td>
                                    <div class="analyst-cell">
                                        <span class="analyst-avatar">{{ strtoupper(substr($analyst->name, 0, 2)) }}</span>
                                        <span>
                                            <strong>{{ $analyst->name }}</strong>
                                            <span class="cell-sub">{{ $analyst->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td><span class="cell-muted">{{ $analyst->phone ?? '—' }}</span></td>
                                <td><span class="pill pill-muted">{{ $analyst->assigned_cases_count }} cases</span></td>
                                <td>
                                    @if($analyst->is_active)
                                        <span class="badge badge-active">Active</span>
                                    @else
                                        <span class="badge badge-inactive">No access</span>
                                    @endif
                                </td>
                                <td class="cell-action">
                                    @if($canManage)
                                        @if($analyst->is_active)
                                            <form method="POST" action="{{ route('admin.users.deactivate', $analyst) }}" class="inline-form">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm">Remove access</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.activate', $analyst) }}" class="inline-form">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">Restore access</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="cell-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        @if(!empty($search))
                                            <h3>No results</h3>
                                            <p>No analysts match “{{ $search }}”. Try another name, email or phone.</p>
                                        @else
                                            <h3>No analysts yet</h3>
                                            <p>Use the form to create the first analyst account.</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card team-panel" id="add-analyst">
                <div class="team-panel-head">
                    <h2>Add analyst</h2>
                </div>
                <form method="POST" action="{{ route('admin.analysts.store') }}" class="team-analyst-form">
                    @csrf
                    <div class="form-field">
                        <label for="analyst-name">Full name</label>
                        <input type="text" id="analyst-name" name="name" value="{{ old('name') }}" required autocomplete="name">
                    </div>
                    <div class="form-field">
                        <label for="analyst-email">Email</label>
                        <input type="email" id="analyst-email" name="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                    <div class="form-field">
                        <label for="analyst-phone">Phone <span class="form-optional">(optional)</span></label>
                        <input type="number" id="analyst-phone" name="phone" value="{{ old('phone') }}" inputmode="numeric" autocomplete="tel" min="0" step="1">
                    </div>
                    <div class="form-field">
                        <label for="analyst-password">Password</label>
                        <input type="password" id="analyst-password" name="password" required autocomplete="new-password" aria-describedby="analyst-password-hint">
                        <p class="form-field-hint" id="analyst-password-hint">{{ \App\Support\PasswordRules::hint() }}</p>
                    </div>
                    <div class="form-field">
                        <label for="analyst-password-confirm">Confirm password</label>
                        <input type="password" id="analyst-password-confirm" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Create analyst account</button>
                </form>
            </section>
        </div>
@endsection
