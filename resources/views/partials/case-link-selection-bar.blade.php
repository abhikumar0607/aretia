@if(!empty($enableCaseLinking) && !empty($linkCasesRoute))
<form method="POST" action="{{ $linkCasesRoute }}" id="case-link-form" class="case-link-form-hidden">
    @csrf
    <div id="case-link-inputs"></div>
</form>

<div class="case-link-bar" id="case-link-bar" hidden>
    <div class="case-link-bar-inner">
        <p class="case-link-bar-text">
            <strong id="case-link-count">0</strong> cases selected
        </p>
        <div class="case-link-bar-actions">
            <button type="button" class="btn btn-secondary btn-sm" id="case-link-clear">Clear</button>
            <button type="submit" form="case-link-form" class="btn btn-primary btn-sm" id="case-link-submit" disabled>
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Link as related cases
            </button>
        </div>
    </div>
</div>
@endif
