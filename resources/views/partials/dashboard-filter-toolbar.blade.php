@php
    /** @var \App\Support\DashboardFilters $dashboardFilters */
    $filterAction = $filterAction ?? url()->current();
@endphp
<form method="GET" action="{{ $filterAction }}" class="dashboard-filter-toolbar portal-filter-toolbar" id="dashboard-filters-form" data-portal-period-form>
    <label class="portal-filter-toolbar-label" for="dashboard-filter-period">Time period</label>

    <div class="portal-filter-toolbar-controls">
        @include('partials.portal-period-filter', [
            'periodFilters' => $dashboardFilters,
            'periodSelectId' => 'dashboard-filter-period',
            'customDatesId' => 'dashboard-custom-dates',
        ])

        <button type="submit" class="btn btn-primary btn-sm listing-filter-btn portal-filter-apply-btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Apply
        </button>

        @unless($dashboardFilters->isDefault())
            <a href="{{ $filterAction }}?period={{ \App\Support\DashboardFilters::PERIOD_ALL }}" class="portal-filter-reset-link">Reset</a>
        @endunless
    </div>
</form>
