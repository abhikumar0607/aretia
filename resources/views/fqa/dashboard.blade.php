@extends('layouts.portal')

@section('title', 'Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [
    'dashboardFilters' => $dashboardFilters,
    'filterAction' => \App\Support\PortalRoute::route('dashboard'),
    'heading' => 'Overview',
    'description' => ($portalTitle ?? auth()->user()->role->label()).' — your assigned workload and case progress.',
    'statCards' => [
        ['label' => 'Assigned cases', 'value' => $stats['active_cases'], 'accent' => true],
        ['label' => 'Reports delivered', 'value' => $stats['reports_delivered']],
    ],
    'charts' => $charts,
])

@endsection

