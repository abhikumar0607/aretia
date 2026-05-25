@extends('layouts.portal')
@section('title', 'Reports')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Reports</h1>
        <p>All delivered reports for your company{{ auth()->user()->company ? ' ('.auth()->user()->company->name.')' : '' }} — shared across your team.</p>
    </div>
</header>

<div class="listing-stats">
    <div class="listing-stat">
        <span class="listing-stat-value">{{ $stats['total'] }}</span>
        <span class="listing-stat-label">Total reports</span>
    </div>
    <div class="listing-stat listing-stat-success">
        <span class="listing-stat-value">{{ $stats['month'] }}</span>
        <span class="listing-stat-label">This month</span>
    </div>
</div>

<div class="listing-panel">
    @include('partials.listing-toolbar', [
        'action' => route('client.reports.index'),
        'placeholder' => 'Search report title or case…',
        'preserve' => ['q'],
    ])

    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Report</th>
                    <th>Case</th>
                    <th>Package</th>
                    <th>Delivered</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($reports as $report)
                <tr class="data-table-row" onclick="window.location='{{ route('client.reports.show', $report) }}'">
                    <td>
                        <div class="cell-primary cell-with-icon">
                            <span class="file-icon file-icon-pdf">
                                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <div>
                                <span class="cell-ref">{{ $report->title }}</span>
                                @if($report->is_password_protected)
                                    <span class="cell-sub">Password protected</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td><span class="cell-ref-sm">{{ $report->caseFile->reference }}</span></td>
                    <td><span class="pill pill-package">{{ $report->caseFile->order->package->name }}</span></td>
                    <td><span class="cell-date">{{ $report->delivered_at->format('d M Y') }}</span></td>
                    <td class="cell-action">
                        <a href="{{ route('client.reports.show', $report) }}" class="btn btn-primary btn-sm" onclick="event.stopPropagation()">Download</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3>No reports yet</h3>
                            <p>Reports appear here once your case is completed and delivered.</p>
                            <a href="{{ route('client.cases.index') }}" class="btn btn-secondary" style="margin-top:1.25rem;">View cases</a>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $reports->links() }}
</div>
@endsection
