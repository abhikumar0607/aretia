@extends('layouts.portal')

@section('title', 'Super Admin Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'heading' => 'Super Admin',

    'description' => 'System overview and full platform control.',

    'statCards' => [

        ['label' => 'Pending onboarding', 'value' => $stats['pending_onboarding'], 'accent' => true],

        ['label' => 'Total orders', 'value' => $stats['orders']],

        ['label' => 'Open cases', 'value' => $stats['open_cases']],

        ['label' => 'Reports delivered', 'value' => $stats['reports_ready']],

    ],

    'charts' => $charts,

    'quickLinks' => [

        ['title' => 'Onboarding', 'text' => 'Review KYC & activate clients', 'route' => 'admin.onboarding.index'],

        ['title' => 'Orders', 'text' => 'All client orders', 'route' => 'admin.orders.index'],

        ['title' => 'Cases', 'text' => 'Assign analysts & workflows', 'route' => 'admin.cases.index'],

        ['title' => 'Audit trail', 'text' => 'Compliance logs', 'route' => 'admin.audit.index'],

    ],

])

@endsection

