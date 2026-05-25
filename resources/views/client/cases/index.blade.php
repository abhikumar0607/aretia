@extends('layouts.portal')
@section('title', 'My Cases')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>My cases</h1>
        <p>All cases for your company{{ auth()->user()->company ? ' ('.auth()->user()->company->name.')' : '' }} — not limited to who placed the order.</p>
    </div>
</header>

<div class="listing-stats">
    <div class="listing-stat">
        <span class="listing-stat-value">{{ $stats['total'] }}</span>
        <span class="listing-stat-label">All cases</span>
    </div>
    <div class="listing-stat listing-stat-accent">
        <span class="listing-stat-value">{{ $stats['in_progress'] }}</span>
        <span class="listing-stat-label">In progress</span>
    </div>
    <div class="listing-stat listing-stat-success">
        <span class="listing-stat-value">{{ $stats['completed'] }}</span>
        <span class="listing-stat-label">Report delivered</span>
    </div>
</div>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route('client.cases.index'),
        'placeholder' => 'Search case or order reference…',
        'filters' => [[
            'name' => 'stage',
            'label' => 'All stages',
            'options' => $stageOptions,
        ]],
        'preserve' => ['q', 'stage'],
    ])

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Case</th>
                    <th>Package</th>
                    <th>Subject</th>
                    <th>Stage</th>
                    <th>Analyst</th>
                    <th>Chat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                @php
                    $stageColor = $case->stage?->color ?? '#6366f1';
                    $hasReport = $case->latestReport?->delivered_at;
                @endphp
                <tr class="data-table-row" onclick="window.location='{{ route('client.cases.show', $case) }}'">
                    <td>
                        <div class="cell-primary">
                            <span class="cell-ref">{{ $case->reference }}</span>
                            <span class="cell-sub">{{ $case->order->reference }}</span>
                        </div>
                    </td>
                    <td><span class="pill pill-package">{{ $case->order->package->name }}</span></td>
                    <td><span class="cell-muted">{{ $case->order->subject_name ?? 'Custom' }}</span></td>
                    <td>
                        @if($case->stage)
                            <span class="stage-pill" style="--stage-color: {{ $stageColor }}">{{ $case->stage->name }}</span>
                        @else
                            <span class="cell-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($case->assignee)
                            <div class="analyst-cell">
                                <span class="analyst-avatar">{{ strtoupper(substr($case->assignee->name, 0, 2)) }}</span>
                                <span>{{ $case->assignee->name }}</span>
                            </div>
                        @else
                            <span class="pill pill-muted">Unassigned</span>
                        @endif
                    </td>
                    <td class="cell-action" onclick="event.stopPropagation()">
                        @if($case->assignee)
                            <a href="{{ route('client.cases.show', $case) }}?chat=1" class="btn btn-secondary btn-sm case-list-chat-btn" title="Message analyst">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                Chat
                            </a>
                        @else
                            <span class="cell-muted" title="Chat opens after an analyst is assigned">—</span>
                        @endif
                    </td>
                    <td class="cell-action">
                        @if($hasReport)
                            <span class="pill pill-success">Report ready</span>
                        @endif
                        <a href="{{ route('client.cases.show', $case) }}" class="row-link" onclick="event.stopPropagation()">
                            Open
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            </div>
                            <h3>No cases yet</h3>
                            <p>Cases are created automatically when you confirm an order.</p>
                            <a href="{{ route('client.orders.create') }}" class="btn btn-primary" style="margin-top:1.25rem;">Place an order</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $cases->links() }}
</div>
@endsection
