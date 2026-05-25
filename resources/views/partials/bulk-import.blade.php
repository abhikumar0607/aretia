<a href="{{ $backRoute }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    {{ $backLabel }}
</a>

<header class="listing-hero import-hero">
    <div class="listing-hero-text">
        <h1>{{ $title }}</h1>
        <p>{{ $subtitle }}</p>
    </div>
</header>

<div class="import-steps">
    <div class="import-step-card">
        <div class="import-step-badge">1</div>
        <div class="import-step-body">
            <h3>Download template</h3>
            <p>{{ $step1Hint }}</p>
            <a href="{{ $templateRoute }}" class="btn btn-secondary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download Excel template
            </a>
        </div>
    </div>

    <div class="import-step-card import-step-card-accent">
        <div class="import-step-badge">2</div>
        <div class="import-step-body">
            <h3>Upload &amp; import</h3>
            <p>Excel (.xlsx, .xls) or CSV — each row creates one order and case.</p>
            <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="import-upload-form">
                @csrf
                <div class="import-file-zone" data-dropzone>
                    <input type="file" id="import_file" name="file" accept=".xlsx,.xls,.csv" required>
                    <div class="import-file-zone-inner">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span class="import-file-label">Drop file here or <strong>browse</strong></span>
                        <span class="import-file-name" data-file-name></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg import-submit-btn">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import orders
                </button>
            </form>
        </div>
    </div>
</div>

<div class="import-guide-panel">
    <div class="import-guide-section card">
        <div class="detail-section-head">
            <h3>Column guide</h3>
            <span class="pill pill-muted">Required fields marked</span>
        </div>
        <div class="data-table-wrap">
            <table class="data-table import-guide-table">
                <thead>
                    <tr>
                        <th>Column</th>
                        <th>Required</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($columns as $col)
                        <tr>
                            <td><code class="import-code">{{ $col['name'] }}</code></td>
                            <td>
                                @if(($col['required'] ?? '') === 'Yes')
                                    <span class="pill pill-package">Yes</span>
                                @else
                                    <span class="cell-muted">{{ $col['required'] }}</span>
                                @endif
                            </td>
                            <td><span class="cell-muted">{{ $col['example'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="import-packages-section card">
        <div class="detail-section-head">
            <h3>{{ $packagesTitle ?? 'Available packages' }}</h3>
            <span class="pill pill-muted">{{ count($packages) }} packages</span>
        </div>
        <ul class="import-package-list">
            @foreach($packages as $pkg)
                <li class="import-package-item">
                    <code class="import-code">{{ $pkg->slug }}</code>
                    <div class="import-package-info">
                        <strong>{{ $pkg->name }}</strong>
                        <span>
                            @if($pkg->due_days)
                                {{ $pkg->due_days }} business days
                            @else
                                Due date TBD
                            @endif
                        </span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
