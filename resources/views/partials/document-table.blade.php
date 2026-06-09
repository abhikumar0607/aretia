@php
    /** @var \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $documents */
    $showCategory = $showCategory ?? true;
    $tableClass = trim('data-table doc-table '.($tableClass ?? ''));
@endphp

<div class="doc-table-panel{{ !empty($wrapClass) ? ' '.$wrapClass : '' }}"@if(!empty($marginBottom)) style="margin-bottom:{{ $marginBottom }}"@endif>
<div class="data-table-wrap doc-table-wrap">
    <table class="{{ $tableClass }}">
        <thead>
            <tr>
                <th>File</th>
                <th>Uploaded by</th>
                @if($showCategory)
                    <th>Category</th>
                @endif
                <th>Date</th>
                <th class="doc-table-actions-head"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($documents as $doc)
            @php $doc->loadMissing('uploader'); @endphp
            <tr>
                <td>
                    <span class="doc-table-file">
                        <span class="file-icon file-icon-pdf file-icon-sm">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <span class="doc-table-filename" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                    </span>
                </td>
                <td>
                    <span class="cell-muted">
                        {{ $doc->uploader ? $doc->uploader->role->label().' · '.$doc->uploader->name : '—' }}
                    </span>
                </td>
                @if($showCategory)
                    <td><span class="cell-muted">{{ $doc->category ?? 'general' }}</span></td>
                @endif
                <td><span class="cell-date">{{ $doc->created_at->format('d M Y') }}</span></td>
                <td class="doc-table-actions">
                    @if(!empty($order))
                        @if(!empty($documentPreviewRoute) || !empty($documentDownloadRoute))
                            <div class="doc-item-actions">
                                @if(!empty($documentPreviewRoute))
                                    <a href="{{ route($documentPreviewRoute, [$order, $doc]) }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">Preview</a>
                                @endif
                                @if(!empty($documentDownloadRoute))
                                    <a href="{{ route($documentDownloadRoute, [$order, $doc]) }}" class="btn btn-secondary btn-sm">Download</a>
                                @endif
                            </div>
                        @endif
                    @else
                        @include('partials.document-actions', ['doc' => $doc])
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $showCategory ? 5 : 4 }}" class="case-empty-hint" style="text-align:center;padding:1.25rem;">
                    {{ $emptyMessage ?? 'No documents uploaded yet.' }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($documents instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $documents->hasPages())
    {{ $documents->links() }}
@endif
</div>
