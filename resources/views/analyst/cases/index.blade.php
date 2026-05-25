@extends('layouts.portal')
@section('title', 'My Cases')
@section('container_class', 'page-container-wide')
@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Assigned cases</h1>
        <p>Cases assigned to you for review and delivery.</p>
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
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr class="data-table-row" onclick="window.location='{{ route('analyst.cases.show', $case) }}'">
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
                    <td class="cell-action">
                        <a href="{{ route('analyst.cases.show', $case) }}" class="btn btn-secondary btn-sm" onclick="event.stopPropagation()">Open</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <h3>No assigned cases</h3>
                            <p>Your admin will assign cases to you here.</p>
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
