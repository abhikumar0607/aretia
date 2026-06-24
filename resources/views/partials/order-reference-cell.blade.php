<div class="cell-primary">
    <a href="{{ $url }}" class="row-link" @if(!empty($stopPropagation)) onclick="event.stopPropagation()" @endif>
        <span class="cell-ref">{{ $order->reference }}</span>
    </a>
    @if($order->caseFile)
        <span class="cell-sub">Case linked</span>
    @endif
    @if($order->marked_as_duplicate)
        <span class="pill pill-muted case-linked-pill" title="Marked as duplicate">Duplicate</span>
    @endif
</div>
