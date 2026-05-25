@extends('layouts.portal')
@section('title', 'Orders')
@section('container_class', 'page-container-wide')
@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>All orders</h1>
        <p>Every client order across the platform.</p>
    </div>
    <div class="listing-hero-actions">
        <a href="{{ route('admin.orders.import') }}" class="btn btn-primary">Bulk import (Excel)</a>
    </div>
</header>

<div class="listing-panel">
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Company</th>
                    <th>Package</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($orders as $order)
                <tr class="data-table-row">
                    <td><span class="cell-ref">{{ $order->reference }}</span></td>
                    <td>{{ $order->company->name }}</td>
                    <td><span class="pill pill-package">{{ $order->package->name }}</span></td>
                    <td><span class="cell-date">{{ $order->due_date?->format('d M Y') ?? 'TBD' }}</span></td>
                    <td class="cell-action">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-secondary btn-sm">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <h3>No orders yet</h3>
                            <p>Orders will appear here when clients place them.</p>
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
