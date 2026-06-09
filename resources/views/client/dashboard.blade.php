@extends('layouts.portal')

@section('title', 'Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'dashboardFilters' => $dashboardFilters,

    'filterAction' => route('client.dashboard'),

    'heading' => 'Welcome, '.$user->name,

    'description' => $company
        ? 'Orders, cases, and reports for '.$company->name.' are shared with everyone on your company account.'
        : 'Place due diligence orders and track your cases.',

    'alert' => !$user->isClientActive() ? 'Complete onboarding to start ordering. <a href="'.route('client.onboarding').'">Go to onboarding</a>' : null,

    'statCards' => [

        ['label' => 'Your cases', 'value' => $stats['cases'], 'accent' => true],

        ['label' => 'Reports delivered', 'value' => $stats['reports']],

        ['label' => 'Total orders', 'value' => $stats['orders']],

    ],

    'charts' => $charts,

])

@endsection

