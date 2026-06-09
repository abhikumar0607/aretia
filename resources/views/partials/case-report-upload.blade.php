@php
    $deliveredReports = collect(
        $case->relationLoaded('report')
            ? $case->report
            : $case->report()->whereNotNull('delivered_at')->with('uploader')->latest('delivered_at')->get()
    )->filter(fn ($report) => $report->delivered_at);

    $latestDelivered = $deliveredReports->sortByDesc('delivered_at')->first();
    $hasDelivered = $latestDelivered !== null;
@endphp

<section class="case-action-card card case-action-card-accent">
    <div class="case-panel-head">
        <h3>Deliver report</h3>
        @if($hasDelivered)
            <span class="pill pill-success">Already delivered</span>
        @else
            <span class="pill pill-package">Notify client</span>
        @endif
    </div>

    @if($hasDelivered)
        <div id="report-delivered-notice" class="case-report-delivered-notice">
            <p class="case-report-delivered-text">
                A final report was already sent to the client
                @if($deliveredReports->count() > 1)
                    ({{ $deliveredReports->count() }} deliveries on this case).
                @else
                    .
                @endif
            </p>
            <div class="case-report-delivered-latest">
                <strong>{{ $latestDelivered->title }}</strong>
                <span class="doc-upload-meta">
                    {{ $latestDelivered->original_name }}
                    &middot; Delivered {{ $latestDelivered->delivered_at->format('d M Y, H:i') }}
                    @if($latestDelivered->is_password_protected)
                        &middot; Password protected
                    @endif
                </span>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="report-send-again-btn">Send again</button>
        </div>
    @endif

    <form
        method="POST"
        action="{{ $storeRoute }}"
        id="report-upload-form"
        class="case-action-form"
        @if($hasDelivered) hidden @endif
        @if($hasDelivered)
            data-previous-report="{{ json_encode([
                'title' => $latestDelivered->title,
                'original_name' => $latestDelivered->original_name,
                'delivered_at' => $latestDelivered->delivered_at->format('d M Y, H:i'),
                'is_password_protected' => $latestDelivered->is_password_protected,
                'file_password' => $latestDelivered->file_password ?? '',
            ]) }}"
        @endif
    >
        @csrf
        @if($hasDelivered)
            <div id="report-resend-context" class="case-report-resend-context">
                <p class="case-report-resend-heading">Resend using previous report details</p>
                <dl class="case-report-resend-dl">
                    <div class="case-report-resend-row">
                        <dt>Previous title</dt>
                        <dd id="report-resend-title">{{ $latestDelivered->title }}</dd>
                    </div>
                    <div class="case-report-resend-row">
                        <dt>Previous file</dt>
                        <dd id="report-resend-file">{{ $latestDelivered->original_name }}</dd>
                    </div>
                    <div class="case-report-resend-row">
                        <dt>Delivered</dt>
                        <dd id="report-resend-delivered">{{ $latestDelivered->delivered_at->format('d M Y, H:i') }}</dd>
                    </div>
                </dl>
                <p class="form-field-hint">Fields below are pre-filled from the last delivery. Update them and choose a new file to send again.</p>
            </div>
        @endif
        <div class="form-field">
            <label for="report_title">Report title</label>
            <input
                type="text"
                name="title"
                id="report_title"
                placeholder="e.g. Due diligence report — Q1 2026"
                value="{{ $hasDelivered ? $latestDelivered->title : '' }}"
                required
            >
        </div>
        <div class="form-field">
            <label>Report file (PDF/DOC/ZIP)</label>
            <p class="form-field-hint">Max {{ \App\Services\PublicUploadService::MAX_MB }} MB — multiple files allowed</p>
            @if($hasDelivered)
                <p class="case-report-previous-file-hint">
                    Last sent file: <strong>{{ $latestDelivered->original_name }}</strong> — pick a new file to replace it.
                </p>
            @endif
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
                <input
                    type="checkbox"
                    name="is_password_protected"
                    value="1"
                    id="report_password_toggle"
                    @checked($hasDelivered && $latestDelivered->is_password_protected)
                >
                Password protected file
            </label>
        </div>
        <div class="form-field" id="report_password_wrap" @if(!($hasDelivered && $latestDelivered->is_password_protected)) hidden @endif>
            <label for="file_password">File password</label>
            <input
                type="text"
                name="file_password"
                id="file_password"
                placeholder="Password client will use to open file"
                value="{{ $hasDelivered && $latestDelivered->is_password_protected ? $latestDelivered->file_password : '' }}"
            >
        </div>
        <div class="case-report-upload-actions">
            @if($hasDelivered)
                <button type="button" class="btn btn-secondary" id="report-cancel-send-again">Cancel</button>
            @endif
            <button type="submit" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload &amp; notify client
            </button>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const notice = document.getElementById('report-delivered-notice');
    const form = document.getElementById('report-upload-form');
    const sendAgainBtn = document.getElementById('report-send-again-btn');
    const cancelBtn = document.getElementById('report-cancel-send-again');
    const toggle = document.getElementById('report_password_toggle');
    const wrap = document.getElementById('report_password_wrap');
    const titleInput = document.getElementById('report_title');
    const passwordInput = document.getElementById('file_password');
    const fileInput = document.getElementById('report_file');
    const fileNameEl = form?.querySelector('[data-file-name]');

    let previousReport = null;
    if (form?.dataset.previousReport) {
        try {
            previousReport = JSON.parse(form.dataset.previousReport);
        } catch {
            previousReport = null;
        }
    }

    function applyPreviousReport() {
        if (!previousReport) return;

        if (titleInput) titleInput.value = previousReport.title || '';
        if (toggle) toggle.checked = !!previousReport.is_password_protected;
        if (wrap) wrap.hidden = !previousReport.is_password_protected;
        if (passwordInput) passwordInput.value = previousReport.file_password || '';
    }

    function clearReportForm() {
        if (form) form.reset();
        if (wrap) wrap.hidden = true;
        if (fileNameEl) fileNameEl.textContent = '';
        if (fileInput) fileInput.value = '';
    }

    sendAgainBtn?.addEventListener('click', () => {
        if (notice) notice.hidden = true;
        if (form) {
            form.hidden = false;
            applyPreviousReport();
        }
    });

    cancelBtn?.addEventListener('click', () => {
        clearReportForm();
        if (form) form.hidden = true;
        if (notice) notice.hidden = false;
    });

    if (toggle && wrap) {
        toggle.addEventListener('change', () => {
            wrap.hidden = !toggle.checked;
            if (!toggle.checked && passwordInput) passwordInput.value = '';
        });
    }
});
</script>
