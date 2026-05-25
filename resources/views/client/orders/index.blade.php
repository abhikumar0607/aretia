@extends('layouts.portal')
@section('title', 'My Orders')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>My orders</h1>
        <p>Track due diligence requests, due dates, and confirmation status.</p>
    </div>
    <div class="listing-hero-actions">
        <a href="{{ route('client.orders.create') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New order
        </a>
        <a href="{{ route('client.orders.import') }}" class="btn btn-secondary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Bulk import
        </a>
    </div>
</header>

<div class="listing-stats">
    <div class="listing-stat">
        <span class="listing-stat-value">{{ $stats['total'] }}</span>
        <span class="listing-stat-label">Total orders</span>
    </div>
    <div class="listing-stat">
        <span class="listing-stat-value">{{ $stats['pending'] }}</span>
        <span class="listing-stat-label">Pending</span>
    </div>
    <div class="listing-stat">
        <span class="listing-stat-value">{{ $stats['confirmed'] }}</span>
        <span class="listing-stat-label">Confirmed</span>
    </div>
</div>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route('client.orders.index'),
        'placeholder' => 'Search reference or subject…',
        'filters' => [[
            'name' => 'status',
            'label' => 'All statuses',
            'options' => $statusOptions,
        ]],
    ])

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Package</th>
                    <th>Subject</th>
                    <th>Due date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr class="data-table-row" onclick="window.location='{{ route('client.orders.show', $order) }}'">
                    <td>
                        <div class="cell-primary">
                            <span class="cell-ref">{{ $order->reference }}</span>
                            @if($order->caseFile)
                                <span class="cell-sub">Case linked</span>
                            @endif
                        </div>
                    </td>
                    <td><span class="pill pill-package">{{ $order->package->name }}</span></td>
                    <td>
                        <span class="cell-muted">{{ $order->subject_name ?? ($order->custom_request ? 'Custom request' : '—') }}</span>
                    </td>
                    <td>
                        <span class="cell-date">{{ $order->due_date?->format('d M Y') ?? 'TBD' }}</span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $order->status->value }}">{{ $order->status->value }}</span>
                    </td>
                    <td class="cell-action">
                        <a href="{{ route('client.orders.show', $order) }}" class="row-link" onclick="event.stopPropagation()">
                            View
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <h3>No orders yet</h3>
                            <p>Place your first due diligence order or import from Excel.</p>
                            <div style="margin-top:1.25rem;display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                                <a href="{{ route('client.orders.create') }}" class="btn btn-primary">New order</a>
                                <a href="{{ route('client.orders.import') }}" class="btn btn-secondary">Bulk import</a>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $orders->links() }}
</div>
@endsection
