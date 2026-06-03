@php
    use App\Support\DashboardFilters;
    /** @var DashboardFilters|null $periodFilters */
    $periodFilters = $periodFilters ?? DashboardFilters::fromRequestQuery(request());
    $period = $periodFilters->period;
    $dateFromVal = $periodFilters->dateFrom;
    $dateToVal = $periodFilters->dateTo;
    $showCustomDates = $period === DashboardFilters::PERIOD_CUSTOM;
    $periodSelectId = $periodSelectId ?? 'filter-period';
    $customDatesId = $customDatesId ?? 'filter-custom-dates';
@endphp
<div class="listing-select-wrap portal-filter-select-wrap">
    <select
        name="period"
        id="{{ $periodSelectId }}"
        class="listing-filter-select"
        data-portal-period-select
        aria-label="Time period"
    >
        @foreach(DashboardFilters::periodOptions() as $option)
            <option value="{{ $option['value'] }}" @selected($period === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
    <span class="listing-select-chevron" aria-hidden="true">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </span>
</div>

<div
    class="portal-filter-custom-wrap {{ $showCustomDates ? '' : 'is-hidden' }}"
    id="{{ $customDatesId }}"
    data-portal-custom-dates
    @if(!$showCustomDates) hidden @endif
>
    <div
        class="portal-period-date-range {{ ($dateFromVal || $dateToVal) ? 'is-active' : '' }}"
        data-portal-inline-dates
        data-date-dropdown
    >
        <div class="portal-period-date-range-fields listing-date-dropdown-panel" data-date-dropdown-panel>
            <div class="listing-date-dropdown-field">
                <span class="listing-date-dropdown-field-label">From</span>
                <div class="listing-date-dropdown-input-wrap">
                    <input type="date" name="date_from" value="{{ $dateFromVal }}" class="listing-date-dropdown-input" data-due-from aria-label="Date from">
                    <button type="button" class="listing-date-dropdown-cal-btn" aria-label="Open calendar" data-date-picker-trigger>
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                </div>
            </div>
            <div class="listing-date-dropdown-field">
                <span class="listing-date-dropdown-field-label">To</span>
                <div class="listing-date-dropdown-input-wrap">
                    <input type="date" name="date_to" value="{{ $dateToVal }}" class="listing-date-dropdown-input" data-due-to aria-label="Date to">
                    <button type="button" class="listing-date-dropdown-cal-btn" aria-label="Open calendar" data-date-picker-trigger>
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
