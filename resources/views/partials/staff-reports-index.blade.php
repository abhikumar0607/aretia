@php
    $isEmployeeList = in_array($routePrefix, ['analyst', 'qa', 'fqa'], true);
@endphp
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Reports</h1>
        <p>{{ $isEmployeeList ? 'Delivered final reports on cases assigned to you.' : 'All reports delivered by FQA to clients across the platform.' }}</p>
    </div>
</header>

<div class="listing-stats">
    <div class="listing-stat">
        <span class="listing-stat-value">{{ $stats['total'] }}</span>
        <span class="listing-stat-label">Total delivered</span>
    </div>
    <div class="listing-stat listing-stat-success">
        <span class="listing-stat-value">{{ $stats['month'] }}</span>
        <span class="listing-stat-label">This month</span>
    </div>
</div>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route($routePrefix.'.reports.index'),
        'placeholder' => 'Search report, case, subject or FQA…',
        'filters' => [[
            'name' => 'company',
            'label' => 'All companies',
            'options' => $companyOptions,
        ]],
        'showPeriodFilter' => true,
        'preserve' => ['q', 'company', 'period', 'date_from', 'date_to'],
    ])

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Report</th>
                    <th>Company</th>
                    <th>Case</th>
                    <th>Subject</th>
                    <th>Submitted by</th>
                    <th>Delivered</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($reports as $report)
                <tr class="data-table-row" onclick="window.location='{{ route($routePrefix.'.reports.show', $report) }}'">
                    <td>
                        <div class="cell-primary cell-with-icon">
                            <span class="file-icon file-icon-pdf">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <div>
                                <span class="cell-ref">{{ $report->title }}</span>
                                @if($report->is_password_protected)
                                    <span class="cell-sub">Password protected for client</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>{{ $report->caseFile->company?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ route($routePrefix.'.cases.show', $report->caseFile) }}" class="row-link" onclick="event.stopPropagation()">
                            <span class="cell-ref-sm">{{ $report->caseFile->reference }}</span>
                        </a>
                    </td>
                    <td><span class="cell-subject">{{ $report->caseFile->order->subject_name ?? 'Custom' }}</span></td>
                    <td>{{ $report->uploader?->displayNameWithRole() ?? '—' }}</td>
                    <td><span class="cell-date">{{ $report->delivered_at->format('d M Y') }}</span></td>
                    <td class="cell-action">
                        <a href="{{ route($routePrefix.'.reports.show', $report) }}" class="btn btn-secondary btn-sm" onclick="event.stopPropagation()">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            @if(\App\Support\ReportListFilters::hasActiveFilters(request()))
                                <h3>No results</h3>
                                <p>No reports match your filters.</p>
                            @else
                                <h3>No reports yet</h3>
                                <p>{{ $isEmployeeList ? 'Reports appear here when a final report is delivered on one of your assigned cases.' : 'Reports appear here when FQA delivers them on a case.' }}</p>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</div>
