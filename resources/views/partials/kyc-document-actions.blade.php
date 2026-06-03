@php
    /** @var \App\Models\KycDocument $doc */
@endphp
<div class="doc-item-actions">
    <a href="{{ route('client.onboarding.document', $doc) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">Preview</a>
    <a href="{{ route('client.onboarding.document', ['kyc' => $doc, 'download' => 1]) }}" class="btn btn-secondary btn-sm">Download</a>
</div>
