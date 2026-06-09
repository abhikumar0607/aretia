@php
    $column = $column ?? '';
    $label = $label ?? '';
    $isActive = \App\Support\CaseListSorting::isActive($column);
    $dir = \App\Support\CaseListSorting::activeDir();
@endphp
<th class="data-table-sortable @if($isActive) is-sorted is-sorted--{{ $dir }} @endif" scope="col">
    <a href="{{ \App\Support\CaseListSorting::sortUrl($column) }}" class="data-table-sort-link">
        <span>{{ $label }}</span>
        <span class="data-table-sort-icons" aria-hidden="true">
            <svg class="data-table-sort-icon data-table-sort-icon--up" width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><path d="M5 1.5 8.5 6H1.5L5 1.5z"/></svg>
            <svg class="data-table-sort-icon data-table-sort-icon--down" width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><path d="M5 8.5 1.5 4h7L5 8.5z"/></svg>
        </span>
    </a>
</th>
