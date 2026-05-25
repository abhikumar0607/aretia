@extends('layouts.portal')

@section('title', 'Admin Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'heading' => 'Admin dashboard',

    'description' => 'Manage onboarding, orders, cases, and compliance.',

    'statCards' => [

        ['label' => 'Pending onboarding', 'value' => $stats['pending_onboarding']],

        ['label' => 'Total orders', 'value' => $stats['orders']],

        ['label' => 'Open cases', 'value' => $stats['open_cases']],

        ['label' => 'Reports delivered', 'value' => $stats['reports_ready']],

    ],

    'charts' => $charts,

    'quickLinks' => [

        ['title' => 'Onboarding', 'text' => 'Review KYC submissions', 'route' => 'admin.onboarding.index'],

        ['title' => 'Orders', 'text' => 'View all client orders', 'route' => 'admin.orders.index'],

        ['title' => 'Cases', 'text' => 'Assign analysts & track stages', 'route' => 'admin.cases.index'],

        ['title' => 'Audit trail', 'text' => 'Compliance activity log', 'route' => 'admin.audit.index'],

    ],

])

@endsection

