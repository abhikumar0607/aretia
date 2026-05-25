@extends('layouts.portal')
@section('title', 'Audit Trail')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Audit trail</h1>
        <p>Who did what, on whom, and when — compliance activity across the portal.</p>
    </div>
</header>

<div class="listing-panel">
    <div class="listing-panel-head">
        <h2>Activity log</h2>
    </div>

    <div class="data-table-wrap">
        <table class="data-table audit-table">
            <thead>
                <tr>
                    <th style="width: 220px;">Performed by</th>
                    <th style="width: 200px;">Action</th>
                    <th>Target &amp; details</th>
                    <th style="width: 170px;">Date &amp; time</th>
                    <th style="width: 130px;">IP address</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                @php
                    $chips = $log->detailChips();
                    $targetLabel = $log->targetLabel();
                    $targetKind = $log->targetKind();
                @endphp
                <tr class="data-table-row">
                    <td>
                        <div class="audit-user-cell">
                            <span class="analyst-avatar">{{ strtoupper(substr($log->user?->name ?? 'SY', 0, 2)) }}</span>
                            <div>
                                <strong class="audit-user-name">{{ $log->user?->name ?? 'System' }}</strong>
                                @if($log->user?->role)
                                    <span class="audit-user-role">{{ $log->user->role->label() }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="pill pill-audit pill-audit-{{ $log->actionTone() }}">{{ $log->actionLabel() }}</span>
                    </td>
                    <td>
                        @if($targetLabel)
                            <div class="audit-target">
                                @if($targetKind)
                                    <span class="audit-target-kind">{{ $targetKind }}</span>
                                @endif
                                <span class="audit-target-label">{{ $targetLabel }}</span>
                            </div>
                        @else
                            <span class="cell-muted">—</span>
                        @endif

                        @if(count($chips))
                            <div class="audit-chips">
                                @foreach($chips as $chip)
                                    <span class="audit-chip">
                                        <span class="audit-chip-label">{{ $chip['label'] }}:</span>
                                        <span class="audit-chip-value">{{ $chip['value'] }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="cell-date">{{ $log->created_at->format('d M Y') }}</span>
                        <span class="cell-sub">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                    <td><span class="cell-muted audit-ip">{{ $log->ip_address ?? '—' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3>No activity yet</h3>
                            <p>Actions across the portal will appear here automatically.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
</div>
@endsection
