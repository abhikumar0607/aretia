@php
    $canReview = in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Rejected], true)
        && ! $order->caseFile;
    $isRejected = $order->status === \App\Enums\OrderStatus::Rejected;
    $canApproveOrders = auth()->user()->hasPermission('orders.approve');
@endphp

@if($canReview && $canApproveOrders)
<div class="card review-actions-card" style="margin-bottom:1.25rem;">
    <h3>{{ $isRejected ? 'Update order status' : 'Order approval' }}</h3>

    @if($isRejected)
        <p class="form-field-hint" style="margin-bottom:1rem;">This order was rejected. You can approve it now to create the case, notify the client, and clear the rejection reason.</p>
    @else
        <p class="form-field-hint" style="margin-bottom:1rem;">Approve to create the case file and notify the client. The case does not exist until you approve.</p>
    @endif

    <div class="review-actions {{ $isRejected ? 'review-actions--approve-only' : '' }}">
        <div class="approve-form">
            <form method="POST" action="{{ $approveRoute }}" id="order-approve-form">
                @csrf
                <button type="submit" hidden aria-hidden="true" tabindex="-1">Submit</button>
            </form>
            <button type="button" class="btn btn-primary btn-lg" style="width:100%;" data-modal-open="order-approve-modal">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ $isRejected ? 'Approve order & create case' : 'Approve order & create case' }}
            </button>
        </div>

        @unless($isRejected)
            <form method="POST" action="{{ $rejectRoute }}" class="reject-form">
                @csrf
                <label>Rejection reason</label>
                <textarea name="rejection_reason" placeholder="Explain why this order was not approved..." required></textarea>
                <button type="submit" class="btn btn-danger btn-lg" style="width:100%;margin-top:0.5rem;">Reject order</button>
            </form>
        @endunless
    </div>
</div>

<div id="order-approve-modal" class="portal-modal" hidden aria-hidden="true">
    <div class="portal-modal-backdrop" data-modal-close aria-hidden="true"></div>
    <div class="portal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="order-approve-modal-title" tabindex="-1" data-modal-focus>
        <div class="portal-modal-icon portal-modal-icon-success" aria-hidden="true">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 id="order-approve-modal-title">{{ $isRejected ? 'Approve this rejected order?' : 'Approve this order?' }}</h3>
        <p>
            @if($isRejected)
                The rejection reason will be cleared, a case file will be created, and the client will be notified by email.
            @else
                A case file will be created and the client will be notified by email.
            @endif
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
@endif
