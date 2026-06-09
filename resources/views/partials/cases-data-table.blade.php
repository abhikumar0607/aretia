@php
    $showTeam = $showTeam ?? false;
    $enableCaseLinking = $enableCaseLinking ?? false;
    $rowClickable = $rowClickable ?? false;
    $manageLabel = $manageLabel ?? 'Manage';
    $colspan = ($enableCaseLinking ? 1 : 0) + 7 + 1 + ($showTeam ? 1 : 0) + 1;
@endphp
<div class="data-table-wrap">
    <table class="data-table" @if($enableCaseLinking) data-case-link-table @endif>
        <thead>
            <tr>
                @if($enableCaseLinking)
                    <th class="cell-checkbox" scope="col">
                        <input type="checkbox" id="case-select-all" class="case-select-all" aria-label="Select all on page">
                    </th>
                @endif
                @include('partials.data-table-sort-th', ['column' => 'reference', 'label' => 'Reference'])
                @include('partials.data-table-sort-th', ['column' => 'subject', 'label' => 'Subject'])
                @include('partials.data-table-sort-th', ['column' => 'company', 'label' => 'Company'])
                <th scope="col">Client</th>
                @include('partials.data-table-sort-th', ['column' => 'package', 'label' => 'Package'])
                @include('partials.data-table-sort-th', ['column' => 'confirmed', 'label' => 'Confirmed'])
                @include('partials.data-table-sort-th', ['column' => 'due_date', 'label' => 'Due date'])
                @include('partials.data-table-sort-th', ['column' => 'stage', 'label' => 'Stage'])
                @if($showTeam)
                    <th scope="col">Team</th>
                @endif
                <th scope="col" aria-label="Actions"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($cases as $case)
            <tr
                class="data-table-row"
                @if($rowClickable) onclick="window.location='{{ \App\Support\PortalRoute::route('cases.show', $case) }}'" @endif
            >
                @if($enableCaseLinking)
                    <td class="cell-checkbox" onclick="event.stopPropagation()">
                        <input type="checkbox" class="case-select-checkbox" value="{{ $case->id }}" aria-label="Select {{ $case->reference }}">
                    </td>
                @endif
                <td>
                    <a href="{{ \App\Support\PortalRoute::route('cases.show', $case) }}" class="row-link" @if($rowClickable) onclick="event.stopPropagation()" @endif>
                        <span class="cell-ref">{{ $case->reference }}</span>
                    </a>
                    @if($case->case_link_group_id)
                        <span class="pill pill-muted case-linked-pill" title="Part of a related case group">Related</span>
                    @endif
                </td>
                <td><span class="cell-subject">{{ $case->order->subject_name ?? 'Custom' }}</span></td>
                <td>{{ $case->company->name }}</td>
                <td>@include('partials.case-client-cell', ['case' => $case])</td>
                <td><span class="pill pill-package">{{ $case->order->package->name }}</span></td>
                <td><span class="cell-date">{{ $case->order->confirmed_at?->format('d M Y') ?? '—' }}</span></td>
                <td><span class="cell-date">{{ $case->portalDueDateLabel() }}</span></td>
                <td>
                    @if($case->stage)
                        <span class="stage-pill" style="--stage-color: {{ $case->stage->color }}">{{ $case->stage->name }}</span>
                    @else
                        <span class="cell-muted">—</span>
                    @endif
                </td>
                @if($showTeam)
                    <td>
                        @if($case->hasFullEmployeeTeam())
                            <span class="cell-sub" title="Lead Analyst: {{ $case->assignee?->name }}">{{ $case->analystTeamNames() }}</span>
                        @elseif($case->assignee)
                            <span class="cell-muted" title="{{ $case->analystTeamNames() }}">Incomplete team</span>
                        @else
                            <span class="cell-muted">—</span>
                        @endif
                    </td>
                @endif
                <td class="cell-action" @if($rowClickable) onclick="event.stopPropagation()" @endif>
                    <a href="{{ \App\Support\PortalRoute::route('cases.show', $case) }}" class="btn btn-secondary btn-sm">{{ $manageLabel }}</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $colspan }}">
                    <div class="empty-state">
                        @if(\App\Support\CaseListFilters::hasActiveFilters(request()))
                            <h3>No results</h3>
                            <p>No cases match your filters.</p>
                        @else
                            <h3>{{ $emptyTitle ?? 'No cases yet' }}</h3>
                            <p>{{ $emptyText ?? 'Cases are created when orders are approved.' }}</p>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
