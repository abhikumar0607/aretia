@extends('layouts.portal')

@section('title', 'Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'dashboardFilters' => $dashboardFilters,

    'filterAction' => route('superadmin.dashboard'),

    'heading' => 'Overview',

    'description' => 'Platform totals for cases and orders in your current filter view.',

    'statCards' => [

        ['label' => 'Total cases', 'value' => $stats['total_cases'], 'accent' => true],

        ['label' => 'Total orders', 'value' => $stats['total_orders']],

        ['label' => 'Confirmed orders', 'value' => $stats['confirmed_orders']],

        ['label' => 'Pending orders', 'value' => $stats['pending_orders'], 'warn' => $stats['pending_orders'] > 0],

    ],

    'charts' => $charts,

])

@endsection

