@if(!empty($markDuplicatesRoute))
<form method="POST" action="{{ $markDuplicatesRoute }}" id="order-duplicate-form" class="case-link-form-hidden">
    @csrf
    <div id="order-duplicate-inputs"></div>
</form>

<div class="case-link-bar" id="order-duplicate-bar" hidden>
    <div class="case-link-bar-inner">
        <p class="case-link-bar-text">
            <strong id="order-duplicate-count">0</strong> orders selected
        </p>
        <div class="case-link-bar-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="order-duplicate-clear">Clear</button>
            <button type="submit" form="order-duplicate-form" class="btn btn-primary btn-sm" id="order-duplicate-submit" disabled>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Mark as duplicate
            </button>
        </div>
    </div>
</div>
@endif
