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

    if (! empty($packageOptions)) {

        $filters[] = [

            'name' => 'package',

            'label' => 'All packages',

            'options' => $packageOptions,

        ];

    }

@endphp

@include('partials.listing-toolbar', [

    'action' => $action,

    'placeholder' => $placeholder ?? 'Search case, subject, order or company…',

    'filters' => $filters,

    'showPeriodFilter' => true,

    'preserve' => ['q', 'company', 'stage', 'package', 'team_user', 'period', 'date_from', 'date_to', 'sort', 'dir'],

    'periodSelectId' => 'cases-filter-period',

    'customDatesId' => 'cases-custom-dates',

])
