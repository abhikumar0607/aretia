@php

    $filters = [];

    if (! empty($companyOptions)) {

        $filters[] = [

            'name' => 'company',

            'label' => 'All companies',

            'options' => $companyOptions,

        ];

    }

    $filters[] = [

        'name' => 'stage',

        'label' => 'All stages',

        'options' => $stageOptions,

    ];

@endphp

@include('partials.listing-toolbar', [

    'action' => $action,

    'placeholder' => $placeholder ?? 'Search case, order or company…',

    'filters' => $filters,

    'showPeriodFilter' => true,

    'preserve' => ['q', 'company', 'stage', 'period', 'date_from', 'date_to'],

    'periodSelectId' => 'cases-filter-period',

    'customDatesId' => 'cases-custom-dates',

])
