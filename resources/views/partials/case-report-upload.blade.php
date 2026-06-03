<section class="case-action-card card case-action-card-accent">
    <div class="case-panel-head">
        <h3>Deliver report</h3>
        <span class="pill pill-package">Notify client</span>
    </div>
    <form method="POST" action="{{ $storeRoute }}" id="report-upload-form" class="case-action-form">
        @csrf
        <div class="form-field">
            <label for="report_title">Report title</label>
            <input type="text" name="title" id="report_title" placeholder="e.g. Due diligence report — Q1 2026" required>
        </div>
        <div class="form-field">
            <label>Report file (PDF/DOC/ZIP)</label>
            <p class="form-field-hint">Max {{ \App\Services\PublicUploadService::MAX_MB }} MB — multiple files allowed</p>
            <div class="import-file-zone order-file-zone" data-dropzone>
                <input type="file" id="report_file" accept=".pdf,.doc,.docx,.zip" multiple>
                <div class="import-file-zone-inner">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="import-file-label">Drop report(s) or <strong>browse</strong></span>
                    <span class="import-file-name" data-file-name></span>
                </div>
            </div>
        </div>
        <div class="form-field case-checkbox-field">
            <label class="case-checkbox-label">
                <input type="checkbox" name="is_password_protected" value="1" id="report_password_toggle">
                Password protected file
            </label>
        </div>
        <div class="form-field" id="report_password_wrap" hidden>
            <label for="file_password">File password</label>
            <input type="text" name="file_password" id="file_password" placeholder="Password client will use to open file">
        </div>
        <button type="submit" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Upload &amp; notify client
        </button>
    </form>
</section>
