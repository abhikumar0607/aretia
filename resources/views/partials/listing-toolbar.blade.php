@php
    use App\Support\DashboardFilters;
    $preserve = $preserve ?? ['q', 'company', 'status', 'period', 'date_from', 'date_to', 'stage', 'package'];
    $periodFilters = DashboardFilters::fromRequestQuery(request());
    $toolbarLabel = $toolbarLabel ?? 'Filters';
    $showPeriodFilter = $showPeriodFilter ?? ! empty($showDueDateRange);
    $hasActiveFilters = collect($preserve)->contains(fn ($k) => request()->filled($k))
        || ($showPeriodFilter && ! $periodFilters->isDefault());
@endphp
<form method="GET" action="{{ $action }}" class="listing-toolbar portal-filter-toolbar" data-portal-period-form>
    @if(empty($hideToolbarLabel))
        <span class="portal-filter-toolbar-label">{{ $toolbarLabel }}</span>
    @endif

    <div class="portal-filter-toolbar-controls">
        @if(empty($hideSearch))
            <div class="listing-search portal-filter-search">
                <svg class="listing-search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder ?? 'Search…' }}" autocomplete="off">
            </div>
        @endif

        @if(!empty($filters))
            @foreach($filters as $filter)
                <div class="listing-select-wrap portal-filter-select-wrap">
                    <select name="{{ $filter['name'] }}" class="listing-filter-select" aria-label="{{ $filter['label'] }}">
                        <option value="">{{ $filter['label'] }}</option>
                        @foreach($filter['options'] as $value => $label)
                            <option value="{{ $value }}" @selected(request($filter['name']) == (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="listing-select-chevron" aria-hidden="true">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </div>
            @endforeach
        @endif

        @if($showPeriodFilter)
            @include('partials.portal-period-filter', [
                'periodFilters' => $periodFilters,
                'periodSelectId' => $periodSelectId ?? 'listing-filter-period',
                'customDatesId' => $customDatesId ?? 'listing-custom-dates',
            ])
        @endif

        <button type="submit" class="btn btn-primary btn-sm listing-filter-btn portal-filter-apply-btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Apply
        </button>

        @if($hasActiveFilters)
            <a href="{{ $action }}" class="portal-filter-reset-link">Reset</a>
        @endif
    </div>
</form>

@if($showPeriodFilter)
    @once
        @push('scripts')
            <script src="{{ asset('js/listing-filters.js') }}" defer></script>
            <script src="{{ asset('js/portal-period-filters.js') }}" defer></script>
        @endpush
    @endonce
@endif
