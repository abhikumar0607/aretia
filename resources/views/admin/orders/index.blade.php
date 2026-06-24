@extends('layouts.portal')

@section('title', 'Orders')

@section('container_class', 'page-container-wide')

@section('content')

<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>All orders</h1>
        <p>Review client submissions and manage orders across the platform.</p>
    </div>
    <div class="listing-hero-actions">
        @perm('orders.create')
        <a href="{{ route('admin.orders.create') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New order
        </a>
        <a href="{{ route('admin.orders.import') }}" class="btn btn-secondary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Bulk import
        </a>
        @endperm
        @if($stats['pending'] > 0)
            <span class="pill pill-package">{{ $stats['pending'] }} awaiting approval</span>
        @endif
    </div>
</header>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route('admin.orders.index'),
        'placeholder' => 'Search reference, company or subject…',
        'filters' => [
            [
                'name' => 'company',
                'label' => 'All companies',
                'options' => $companyOptions,
            ],
            [
                'name' => 'package',
                'label' => 'All packages',
                'options' => $packageOptions,
            ],
            [
                'name' => 'status',
                'label' => 'All statuses',
                'options' => $statusOptions,
            ],
        ],
        'showPeriodFilter' => true,
    ])

    @include('partials.order-duplicate-selection-bar', [
        'markDuplicatesRoute' => route('admin.orders.mark-duplicate'),
    ])

    <div class="data-table-wrap">
        <table class="data-table" data-order-duplicate-table>
            <thead>
                <tr>
                    <th class="cell-checkbox" scope="col">
                        <input type="checkbox" id="order-select-all" class="order-select-all" aria-label="Select all on page">
                    </th>
                    <th>Reference</th>
                    <th>Subject</th>
                    <th>Company</th>
                    <th>Package</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr class="data-table-row">
                    @include('partials.order-duplicate-checkbox', ['order' => $order])
                    <td>
                        @include('partials.order-reference-cell', [
                            'order' => $order,
                            'url' => route('admin.orders.show', $order),
                        ])
                    </td>
                    <td>@include('partials.order-subject-cell', ['order' => $order])</td>
                    <td>{{ $order->company->name }}</td>
                    <td><span class="pill pill-package">{{ $order->package->name }}</span></td>
                    <td><span class="badge {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                    <td><span class="cell-date">{{ $order->due_date?->format('d M Y') ?? 'TBD' }}</span></td>
                    <td class="cell-action">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm">
                            {{ $order->status === \App\Enums\OrderStatus::Pending ? 'Review' : 'View' }}
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            @if(\App\Support\OrderListFilters::hasActiveFilters(request()))
                                <h3>No results</h3>
                                <p>No orders match your filters. Try adjusting search or filters.</p>
                            @else
                                <h3>No orders yet</h3>
                                <p>Orders will appear here when clients place them.</p>
                            @endif
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

@push('scripts')
<script src="{{ asset('js/listing-filters.js') }}" defer></script>
<script src="{{ asset('js/order-duplicate-selection.js') }}" defer></script>
@endpush
