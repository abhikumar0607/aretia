@extends('layouts.portal')
@section('title', 'Reports')
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.staff-reports-index')
@endsection

@push('scripts')
<script src="{{ asset('js/listing-filters.js') }}" defer></script>
@endpush
