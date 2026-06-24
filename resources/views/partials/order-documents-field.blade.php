<div class="form-field order-documents-field">
    <label>Supporting documents</label>
    <p class="form-field-hint">{{ $hint ?? 'PDF, Word, or images — max ' . \App\Services\PublicUploadService::MAX_MB . ' MB each. You can add multiple files.' }}</p>
    <div class="import-file-zone order-file-zone" data-dropzone>
        <input
            type="file"
            id="{{ $inputId ?? 'order_documents' }}"
            @if(!empty($inputName))
                name="{{ $inputName }}"
            @else
                data-order-documents
            @endif
            multiple
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
        >
        <div class="import-file-zone-inner">
            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span class="import-file-label">Drop files here or <strong>browse</strong></span>
            <span class="import-file-name" data-file-name></span>
        </div>
    </div>
</div>
