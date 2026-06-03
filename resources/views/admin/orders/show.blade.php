@extends('layouts.portal')
@section('title', $order->reference)
@section('container_class', 'page-container-wide')

@section('content')
@if($order->status === \App\Enums\OrderStatus::Pending)
<div class="card review-actions-card" style="margin-bottom:1.25rem;">
    <h3>Order approval</h3>
    <p class="form-field-hint" style="margin-bottom:1rem;">Approve to create the case file and notify the client. The case does not exist until you approve.</p>
    <div class="review-actions">
        <div class="approve-form">
            <form method="POST" action="{{ route('admin.orders.approve', $order) }}" id="order-approve-form">
                @csrf
            </form>
            <button type="button" class="btn btn-primary btn-lg" style="width:100%;" data-modal-open="order-approve-modal">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Approve order & create case
            </button>
        </div>
        <form method="POST" action="{{ route('admin.orders.reject', $order) }}" class="reject-form">
            @csrf
            <label>Rejection reason</label>
            <textarea name="rejection_reason" placeholder="Explain why this order was not approved..." required></textarea>
            <button type="submit" class="btn btn-danger btn-lg" style="width:100%;margin-top:0.5rem;">Reject order</button>
        </form>
    </div>
</div>

<div id="order-approve-modal" class="portal-modal" hidden aria-hidden="true">
    <div class="portal-modal-backdrop" data-modal-close aria-hidden="true"></div>
    <div class="portal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="order-approve-modal-title" tabindex="-1" data-modal-focus>
        <div class="portal-modal-icon portal-modal-icon-success" aria-hidden="true">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 id="order-approve-modal-title">Approve this order?</h3>
        <p>
            A case file will be created and the client will be notified by email.
            <span class="portal-modal-ref">{{ $order->reference }}</span>
        </p>
        <div class="portal-modal-actions">
            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
            <button type="button" class="btn btn-primary" data-modal-submit="order-approve-form" data-modal-close-after="order-approve-modal">
                Approve & create case
            </button>
        </div>
    </div>
</div>
@elseif($order->status === \App\Enums\OrderStatus::Rejected)
<div class="alert alert-danger" style="margin-bottom:1.25rem;">
    <span>This order was rejected and will not become a case.</span>
</div>
@endif

@include('partials.order-detail', [
    'order' => $order,
    'showCompany' => true,
    'backRoute' => route('admin.orders.index'),
    'backLabel' => 'All orders',
    'caseRoute' => $order->caseFile ? route('admin.cases.show', $order->caseFile) : null,
    'dueDateAction' => route('admin.orders.due-date', $order),
    'documentPreviewRoute' => 'admin.orders.documents.preview',
    'documentDownloadRoute' => 'admin.orders.documents.download',
    'documentUploadRoute' => route('admin.orders.documents.store', $order),
])
@endsection

@push('scripts')
@if($order->status === \App\Enums\OrderStatus::Pending)
<script src="{{ asset('js/portal-modal.js') }}" defer></script>
@endif
@endpush
