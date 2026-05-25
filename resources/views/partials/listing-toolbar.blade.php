@php
    $preserve = $preserve ?? ['q', 'status', 'stage', 'package'];
@endphp
<form method="GET" action="{{ $action }}" class="listing-toolbar">
    <div class="listing-search">
        <svg class="listing-search-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder ?? 'Search…' }}" autocomplete="off">
    </div>
    @if(!empty($filters))
        <div class="listing-filters">
            @foreach($filters as $filter)
                <select name="{{ $filter['name'] }}" aria-label="{{ $filter['label'] }}">
                    <option value="">{{ $filter['label'] }}</option>
                    @foreach($filter['options'] as $value => $label)
                        <option value="{{ $value }}" @selected(request($filter['name']) == (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @endforeach
        </div>
    @endif
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    @if(collect($preserve)->some(fn ($k) => request()->filled($k)))
        <a href="{{ $action }}" class="listing-clear">Clear</a>
    @endif
</form>
