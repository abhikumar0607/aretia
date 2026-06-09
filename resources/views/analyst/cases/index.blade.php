@extends('layouts.portal')
@section('title', 'My Cases')
@section('container_class', 'page-container-wide')
@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Assigned cases</h1>
        <p>Cases assigned to you for review and delivery.</p>
    </div>
</header>

<div class="listing-panel">
    @include('partials.cases-listing-toolbar', [
        'action' => \App\Support\PortalRoute::route('cases.index'),
        'stageOptions' => $stageOptions,
        'companyOptions' => $companyOptions,
        'packageOptions' => $packageOptions,
    ])

    @include('partials.cases-data-table', [
        'cases' => $cases,
        'rowClickable' => true,
        'manageLabel' => 'Open',
        'emptyTitle' => 'No assigned cases',
        'emptyText' => 'Your admin will assign cases to you here.',
    ])

    {{ $cases->links() }}
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/listing-filters.js') }}" defer></script>
@endpush
