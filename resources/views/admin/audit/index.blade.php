@extends('layouts.portal')
@section('title', 'Audit Trail')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Audit trail</h1>
        <p>Who did what, on whom, and when — compliance activity across the portal.</p>
    </div>
</header>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route('admin.audit.index'),
        'placeholder' => 'Case reference, subject, or performer name…',
        'filters' => [
            [
                'name' => 'company',
                'label' => 'All companies',
                'options' => $companyOptions,
            ],
        ],
        'showPeriodFilter' => true,
        'periodSelectId' => 'audit-filter-period',
        'customDatesId' => 'audit-custom-dates',
        'preserve' => ['company', 'q', 'period', 'date_from', 'date_to'],
    ])

    <div class="listing-panel-head">
        <h2>Activity log</h2>
    </div>

    @include('partials.audit-log-table', ['logs' => $logs])

    {{ $logs->links() }}
</div>
@endsection
