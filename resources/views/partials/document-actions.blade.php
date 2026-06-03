@php
    /** @var \App\Models\Document|\App\Models\OrderDocument $doc */
@endphp
<div class="doc-item-actions">
    <a href="{{ route('documents.preview', $doc) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">Preview</a>
    <a href="{{ route('documents.download', $doc) }}" class="btn btn-secondary btn-sm">Download</a>
</div>
