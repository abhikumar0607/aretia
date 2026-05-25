@extends('layouts.portal')

@section('title', 'Client Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'heading' => 'Welcome, '.$user->name,

    'description' => 'Place due diligence orders and track your cases.',

    'alert' => !$user->isClientActive() ? 'Complete onboarding to start ordering. <a href="'.route('client.onboarding').'">Go to onboarding</a>' : null,

    'statCards' => [

        ['label' => 'Orders', 'value' => $stats['orders']],

        ['label' => 'Cases', 'value' => $stats['cases']],

        ['label' => 'Reports delivered', 'value' => $stats['reports']],

    ],

    'charts' => $charts,

    'quickLinks' => $user->isClientActive() ? [

        ['title' => 'New order', 'text' => 'Place a due diligence order', 'route' => 'client.orders.create'],

        ['title' => 'My orders', 'text' => 'View order history', 'route' => 'client.orders.index'],

        ['title' => 'My cases', 'text' => 'Track case progress', 'route' => 'client.cases.index'],

        ['title' => 'Reports', 'text' => 'Download completed reports', 'route' => 'client.reports.index'],

    ] : [],

])



@if($user->isClientActive())
    <section class="dashboard-section">
        <div class="dashboard-section-title">
            <h2>Choose a service</h2>
            <p>Select a due diligence package to get started</p>
        </div>
        <div class="package-grid dashboard-services-grid">

        @foreach($packages as $package)

            <a href="{{ route('client.orders.create', ['package' => $package->slug]) }}" class="package-card">

                <h3>{{ $package->name }}</h3>

                <p>{{ $package->description }}</p>

                <div class="due">{{ $package->due_days }} business days</div>

            </a>

        @endforeach

        <a href="{{ route('client.orders.create', ['package' => 'custom']) }}" class="package-card custom">

            <h3>Custom Order</h3>

            <p>Describe your custom due diligence request.</p>

            <div class="due">Due date TBD</div>

        </a>

        </div>
    </section>
@endif

@endsection

