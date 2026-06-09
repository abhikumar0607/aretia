@extends('layouts.portal')

@section('title', 'Cases')

@section('container_class', 'page-container-wide')

@section('content')

<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Case management</h1>
        <p>Assign analysts, track stages, and link related cases.</p>
    </div>
</header>

<div class="listing-panel">
    @include('partials.cases-listing-toolbar', [
        'action' => route('admin.cases.index'),
        'stageOptions' => $stageOptions,
        'companyOptions' => $companyOptions,
        'packageOptions' => $packageOptions,
    ])

    @include('partials.case-link-selection-bar', [
        'enableCaseLinking' => $enableCaseLinking ?? false,
        'linkCasesRoute' => $linkCasesRoute ?? null,
    ])

    @include('partials.cases-data-table', [
        'cases' => $cases,
        'showTeam' => true,
        'enableCaseLinking' => $enableCaseLinking ?? false,
    ])

    {{ $cases->links() }}
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/listing-filters.js') }}" defer></script>
@if(!empty($enableCaseLinking))
<script src="{{ asset('js/case-link-selection.js') }}" defer></script>
@endif
@endpush
