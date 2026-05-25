@extends('layouts.portal')

@section('title', 'Cases')

@section('container_class', 'page-container-wide')

@section('content')

<header class="listing-hero">

    <div class="listing-hero-text">

        <h1>Case management</h1>

        <p>Assign analysts and track workflow stages.</p>

    </div>

</header>



<div class="listing-panel">

    <div class="data-table-wrap">

        <table class="data-table">

            <thead>

                <tr>

                    <th>Reference</th>

                    <th>Company</th>

                    <th>Client</th>

                    <th>Package</th>

                    <th>Stage</th>

                    <th>Team</th>

                    <th></th>

                </tr>

            </thead>

            <tbody>

            @forelse($cases as $case)

                <tr class="data-table-row">

                    <td><span class="cell-ref">{{ $case->reference }}</span></td>

                    <td>{{ $case->company->name }}</td>

                    <td>@include('partials.case-client-cell', ['case' => $case])</td>

                    <td><span class="pill pill-package">{{ $case->order->package->name }}</span></td>

                    <td>

                        @if($case->stage)

                            <span class="stage-pill" style="--stage-color: {{ $case->stage->color }}">{{ $case->stage->name }}</span>

                        @else

                            <span class="cell-muted">—</span>

                        @endif

                    </td>

                    <td>
                        @if($case->analysts->count() > 1)
                            <span title="Lead: {{ $case->assignee?->name }}">{{ $case->analystTeamNames() }}</span>
                        @else
                            {{ $case->assignee?->name ?? '—' }}
                        @endif
                    </td>

                    <td class="cell-action">

                        <a href="{{ route('admin.cases.show', $case) }}" class="btn btn-secondary btn-sm">Manage</a>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="7">

                        <div class="empty-state">

                            <h3>No cases yet</h3>

                            <p>Cases are created when orders are confirmed.</p>

                        </div>

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    {{ $cases->links() }}

</div>

@endsection

