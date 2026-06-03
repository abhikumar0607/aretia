@extends('layouts.portal')

@section('title', 'Cases')

@section('container_class', 'page-container-wide')

@section('content')

<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Case management</h1>
        <p>Assign analysts, track stages, and link related cases.</p>
    </div>
</header>

<div class="listing-panel">
    @include('partials.cases-listing-toolbar', [
        'action' => route('admin.cases.index'),
        'stageOptions' => $stageOptions,
        'companyOptions' => $companyOptions,
    ])

    @include('partials.case-link-selection-bar', [
        'enableCaseLinking' => $enableCaseLinking ?? false,
        'linkCasesRoute' => $linkCasesRoute ?? null,
    ])

    <div class="data-table-wrap">
        <table class="data-table" data-case-link-table>
            <thead>
                <tr>
                    @if(!empty($enableCaseLinking))
                        <th class="cell-checkbox">
                            <input type="checkbox" id="case-select-all" class="case-select-all" aria-label="Select all on page">
                        </th>
                    @endif
                    <th>Reference</th>
                    <th>Company</th>
                    <th>Client</th>
                    <th>Package</th>
                    <th>Confirmed</th>
                    <th>Due date</th>
                    <th>Stage</th>
                    <th>Team</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr class="data-table-row">
                    @if(!empty($enableCaseLinking))
                        <td class="cell-checkbox" onclick="event.stopPropagation()">
                            <input type="checkbox" class="case-select-checkbox" value="{{ $case->id }}" aria-label="Select {{ $case->reference }}">
                        </td>
                    @endif
                    <td>
                        <a href="{{ route('admin.cases.show', $case) }}" class="row-link">
                            <span class="cell-ref">{{ $case->reference }}</span>
                        </a>
                        @if($case->case_link_group_id)
                            <span class="pill pill-muted case-linked-pill" title="Part of a related case group">Related</span>
                        @endif
                    </td>
                    <td>{{ $case->company->name }}</td>
                    <td>@include('partials.case-client-cell', ['case' => $case])</td>
                    <td><span class="pill pill-package">{{ $case->order->package->name }}</span></td>
                    <td><span class="cell-date">{{ $case->order->confirmed_at?->format('d M Y') ?? '—' }}</span></td>
                    <td><span class="cell-date">{{ $case->order->due_date?->format('d M Y') ?? 'TBD' }}</span></td>
                    <td>
                        @if($case->stage)
                            <span class="stage-pill" style="--stage-color: {{ $case->stage->color }}">{{ $case->stage->name }}</span>
                        @else
                            <span class="cell-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($case->hasFullEmployeeTeam())
                            <span class="cell-sub" title="Lead Analyst: {{ $case->assignee?->name }}">{{ $case->analystTeamNames() }}</span>
                        @elseif($case->assignee)
                            <span class="cell-muted" title="{{ $case->analystTeamNames() }}">Incomplete team</span>
                        @else
                            <span class="cell-muted">—</span>
                        @endif
                    </td>
                    <td class="cell-action">
                        <a href="{{ route('admin.cases.show', $case) }}" class="btn btn-secondary btn-sm">Manage</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ !empty($enableCaseLinking) ? 10 : 9 }}">
                        <div class="empty-state">
                            @if(\App\Support\CaseListFilters::hasActiveFilters(request()))
                                <h3>No results</h3>
                                <p>No cases match your filters.</p>
                            @else
                                <h3>No cases yet</h3>
                                <p>Cases are created when orders are approved.</p>
                            @endif
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

@push('scripts')
<script src="{{ asset('js/listing-filters.js') }}" defer></script>
@if(!empty($enableCaseLinking))
<script src="{{ asset('js/case-link-selection.js') }}" defer></script>
@endif
@endpush
