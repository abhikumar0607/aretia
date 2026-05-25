<div class="case-workspace-grid case-workspace-grid-single">
    <section class="case-panel-card card">
        <div class="case-panel-head">
            <h3>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Documents
            </h3>
            <span class="pill pill-muted">{{ $case->documents->count() }} file(s)</span>
        </div>

        @if($case->documents->count())
            <div class="detail-doc-list case-doc-list">
                @foreach($case->documents as $doc)
                    <div class="detail-doc-item">
                        <span class="file-icon file-icon-pdf">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div class="detail-doc-body">
                            <strong>{{ $doc->original_name }}</strong>
                            <span>{{ $doc->category ?? 'general' }} &middot; {{ $doc->created_at->format('d M Y') }}</span>
                        </div>
                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-secondary btn-sm">Download</a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="case-empty-hint">No documents uploaded yet.</p>
        @endif

        @if($showUpload ?? true)
            <form method="POST" action="{{ route('cases.documents.store', $case) }}" data-binary-upload class="case-inline-form case-upload-form">
                @csrf
                <p class="form-field-hint">Max 5 MB — PDF, Word, or images</p>
                <div class="import-file-zone order-file-zone" data-dropzone>
                    <input type="file" id="case_document_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="import-file-zone-inner">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span class="import-file-label">Drop file or <strong>browse</strong></span>
                        <span class="import-file-name" data-file-name></span>
                    </div>
                </div>
                <div class="case-upload-row">
                    <input type="text" name="category" placeholder="Category (optional)" class="case-category-input">
                    <button type="submit" class="btn btn-primary btn-sm">Upload</button>
                </div>
            </form>
        @endif
    </section>
</div>

<section class="case-panel-card card case-history-card">
    <div class="case-panel-head">
        <h3>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Stage history
        </h3>
    </div>
    <div class="data-table-wrap">
        <table class="data-table case-history-table">
            <thead>
                <tr>
                    <th>Stage</th>
                    <th>Updated by</th>
                    <th>Notes</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($case->stageHistories as $history)
                <tr>
                    <td>
                        <span class="stage-pill" style="--stage-color: {{ $history->stage->color }}">{{ $history->stage->name }}</span>
                    </td>
                    <td>{{ $history->user->name }}</td>
                    <td><span class="cell-muted">{{ $history->notes ?? '—' }}</span></td>
                    <td><span class="cell-date">{{ $history->created_at->format('d M Y H:i') }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="case-empty-hint" style="text-align:center;padding:1.5rem;">No stage changes recorded yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
