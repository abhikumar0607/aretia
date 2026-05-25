@extends('layouts.portal')

@section('title', 'Analyst Dashboard')
@section('container_class', 'page-container-dashboard')

@section('content')

@include('partials.dashboard-shell', [

    'heading' => 'Analyst dashboard',

    'description' => 'Your assigned workload and case progress.',

    'statCards' => [

        ['label' => 'Assigned cases', 'value' => $stats['assigned_cases']],

        ['label' => 'In progress', 'value' => $stats['in_progress']],

        ['label' => 'Completed', 'value' => $stats['completed']],

    ],

    'charts' => $charts,

    'quickLinks' => [

        ['title' => 'My cases', 'text' => 'View all assigned cases', 'route' => 'analyst.cases.index'],

    ],

])



<div class="card card-flush dashboard-recent-card">

    <div class="dashboard-section-head" style="padding: 1.25rem 1.5rem 0; margin: 0;">

        <h2>Recent cases</h2>

    </div>

    <div class="data-table-wrap">

        <table class="data-table">

            <thead>

                <tr>

                    <th>Reference</th>

                    <th>Company</th>

                    <th>Client</th>

                    <th>Stage</th>

                    <th></th>

                </tr>

            </thead>

            <tbody>

            @forelse($cases as $case)

                <tr class="data-table-row">

                    <td><strong>{{ $case->reference }}</strong></td>

                    <td>{{ $case->company->name }}</td>

                    <td>@include('partials.case-client-cell', ['case' => $case])</td>

                    <td>

                        @if($case->stage)

                            <span class="stage-pill" style="--stage-color: {{ $case->stage->color }}">{{ $case->stage->name }}</span>

                        @else

                            <span class="cell-muted">—</span>

                        @endif

                    </td>

                    <td><a href="{{ route('analyst.cases.show', $case) }}" class="btn btn-secondary btn-sm">Open</a></td>

                </tr>

            @empty

                <tr>

                    <td colspan="5" class="case-empty-hint" style="text-align:center;padding:2rem;">No cases assigned yet.</td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection

