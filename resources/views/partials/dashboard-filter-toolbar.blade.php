@php
    /** @var \App\Support\DashboardFilters $dashboardFilters */
    $filterAction = $filterAction ?? url()->current();
    $showScopeFilters = $showScopeFilters ?? false;
@endphp
<form method="GET" action="{{ $filterAction }}" class="dashboard-filter-toolbar portal-filter-toolbar" id="dashboard-filters-form" data-portal-period-form>
    <label class="portal-filter-toolbar-label" for="dashboard-filter-period">Filters</label>

    <div class="portal-filter-toolbar-controls">
        @if($showScopeFilters)
            <div class="listing-select-wrap portal-filter-select-wrap">
                <select
                    name="team_user"
                    id="dashboard-filter-team-user"
                    class="listing-filter-select dashboard-filter-select"
                    aria-label="Team member"
                >
                    <option value="">All team members</option>
                    @foreach(\App\Support\DashboardFilters::teamMemberOptions() as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $dashboardFilters->teamUserId === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <span class="listing-select-chevron" aria-hidden="true">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </div>

            <div class="listing-select-wrap portal-filter-select-wrap">
                <select
                    name="company_id"
                    id="dashboard-filter-company"
                    class="listing-filter-select dashboard-filter-select"
                    aria-label="Company"
                >
                    <option value="">All companies</option>
                    @foreach(\App\Support\DashboardFilters::companyOptions() as $option)
                        <option value="{{ $option['value'] }}" @selected((string) $dashboardFilters->companyId === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <span class="listing-select-chevron" aria-hidden="true">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
            </div>
        @endif

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
            <a
                href="{{ $filterAction }}?period={{ \App\Support\DashboardFilters::PERIOD_ALL }}&team_user=&company_id="
                class="portal-filter-reset-link"
            >Reset</a>
        @endunless
    </div>

    @if($showScopeFilters && ($dashboardFilters->hasScopeFilters() || ! $dashboardFilters->isDefault()))
        <div class="dashboard-filter-toolbar-tags" aria-label="Active filters">
            @if($dashboardFilters->teamUserLabel())
                <span class="dashboard-filter-tag dashboard-filter-tag-accent">{{ $dashboardFilters->teamUserLabel() }}</span>
            @endif
            @if($dashboardFilters->companyLabel())
                <span class="dashboard-filter-tag dashboard-filter-tag-accent">{{ $dashboardFilters->companyLabel() }}</span>
            @endif
            @unless($dashboardFilters->period === \App\Support\DashboardFilters::PERIOD_ALL)
                <span class="dashboard-filter-tag">{{ $dashboardFilters->periodLabel() }}</span>
            @endunless
        </div>
    @endif
</form>
