<div class="dashboard-page">
    <div class="dashboard-main dashboard-main--solo">
        @if(!empty($dashboardFilters))
            @include('partials.dashboard-filter-toolbar', [
                'dashboardFilters' => $dashboardFilters,
                'filterAction' => $filterAction ?? url()->current(),
                'showScopeFilters' => $showScopeFilters ?? false,
            ])
        @endif

        @include('partials.dashboard-shell-content')
        {!! $afterContent ?? '' !!}
    </div>
</div>

@if(!empty($dashboardFilters))
    @push('scripts')
    <script src="{{ asset('js/listing-filters.js') }}" defer></script>
    <script src="{{ asset('js/portal-period-filters.js') }}" defer></script>
    @endpush
@endif
