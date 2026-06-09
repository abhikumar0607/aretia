@php
    $subjectLabel = $order->subject_name ?? ($fallback ?? 'Custom');
    $subjectClass = ($muted ?? false) ? 'cell-muted' : 'cell-subject';
@endphp
<div class="order-subject-cell">
    <span class="{{ $subjectClass }}">{{ $subjectLabel }}</span>
    @if($order->has_duplicate_subject ?? false)
        <span class="pill pill-duplicate" title="Another order has the same subject">Duplicate</span>
    @endif
</div>
