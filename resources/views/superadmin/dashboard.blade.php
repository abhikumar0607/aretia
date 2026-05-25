@extends('layouts.portal')

@section('title', 'Super Admin Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'heading' => 'Super Admin',

    'description' => 'System overview and full platform control.',

    'statCards' => [

        ['label' => 'Client companies', 'value' => $stats['client_companies'], 'accent' => true],

        ['label' => 'Active clients', 'value' => $stats['active_clients']],

        ['label' => 'Analysts', 'value' => $stats['analysts']],

        ['label' => 'Pending onboarding', 'value' => $stats['pending_onboarding'], 'warn' => $stats['pending_onboarding'] > 0],

        ['label' => 'Total orders', 'value' => $stats['orders']],

        ['label' => 'Open cases', 'value' => $stats['open_cases']],

    ],

    'charts' => $charts,

    'quickLinks' => [

        ['title' => 'Clients', 'text' => $stats['client_companies'].' companies registered', 'route' => 'admin.clients.index'],

        ['title' => 'Analysts', 'text' => $stats['analysts'].' analysts · add new', 'route' => 'admin.analysts.index'],

        ['title' => 'Onboarding', 'text' => 'Review KYC & activate clients', 'route' => 'admin.onboarding.index'],

        ['title' => 'Cases', 'text' => 'Assign analysts & workflows', 'route' => 'admin.cases.index'],

    ],

])

@endsection

