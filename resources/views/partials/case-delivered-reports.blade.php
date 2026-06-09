@php
    $deliveredReports = collect(
        $case->relationLoaded('report')
            ? $case->report
            : $case->report()->whereNotNull('delivered_at')->with('uploader')->latest('delivered_at')->get()
    )->filter(fn ($report) => $report->delivered_at);
    $viewer = auth()->user();
    $access = app(\App\Services\ReportAccessService::class);
    $visibleDeliveredReports = $deliveredReports
        ->filter(fn ($report) => $access->canViewDelivered($report, $viewer))
        ->values();
    $deliveredReportTotal = $visibleDeliveredReports->count();
    $paginatedDeliveredReports = \App\Support\CollectionPaginator::paginate($visibleDeliveredReports, pageName: 'reports_page');
@endphp

@if($deliveredReportTotal)
<section class="case-panel-card card case-delivered-reports">
    <div class="case-panel-head">
        <h3>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Final report{{ $deliveredReportTotal > 1 ? 's' : '' }}
        </h3>
        <span class="pill pill-success">Delivered to client</span>
    </div>

    <div class="doc-table-panel">
    <div class="data-table-wrap doc-table-wrap">
        <table class="data-table doc-table">
            <thead>
                <tr>
                    <th>Report</th>
                    <th>Delivered by</th>
                    <th>File</th>
                    <th>Delivered</th>
                    <th class="doc-table-actions-head"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($paginatedDeliveredReports as $report)
                @php
                    if ($viewer->hasRole(\App\Enums\UserRole::Client)) {
                        $showRoute = route('client.reports.show', $report);
                        $downloadRoute = route('client.reports.download', $report);
                    } elseif ($viewer->hasRole(\App\Enums\UserRole::Admin)) {
                        $showRoute = route('admin.reports.show', $report);
                        $downloadRoute = route('admin.reports.download', $report);
                    } elseif ($viewer->hasRole(\App\Enums\UserRole::SuperAdmin)) {
                        $showRoute = route('superadmin.reports.show', $report);
                        $downloadRoute = route('superadmin.reports.download', $report);
                    } elseif ($viewer->isEmployee()) {
                        $showRoute = \App\Support\PortalRoute::route('reports.show', $report);
                        $downloadRoute = \App\Support\PortalRoute::route('reports.download', $report);
                    } else {
                        $showRoute = null;
                        $downloadRoute = null;
                    }
                @endphp
                <tr>
                    <td>
                        <span class="doc-table-file">
                            <span class="file-icon file-icon-pdf file-icon-sm">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span class="doc-table-filename" title="{{ $report->title }}">{{ $report->title }}</span>
                        </span>
                    </td>
                    <td><span class="cell-muted">{{ $report->uploader?->displayNameWithRole() ?? '—' }}</span></td>
                    <td>
                        <span class="cell-muted doc-table-filename" title="{{ $report->original_name }}">{{ $report->original_name }}</span>
                        @if($report->is_password_protected && $viewer->hasRole(\App\Enums\UserRole::Client))
                            <span class="pill pill-muted" style="margin-left:0.35rem;font-size:0.68rem;">Password</span>
                        @endif
                    </td>
                    <td><span class="cell-date">{{ $report->delivered_at->format('d M Y, H:i') }}</span></td>
                    <td class="doc-table-actions">
                        <div class="doc-item-actions">
                            @if($showRoute)
                                <a href="{{ $showRoute }}" class="btn btn-secondary btn-sm">View</a>
                            @endif
                            @if($downloadRoute && $viewer->hasRole(\App\Enums\UserRole::Client) && $report->is_password_protected)
                                <a href="{{ $showRoute }}" class="btn btn-primary btn-sm">Download</a>
                            @elseif($downloadRoute && $viewer->hasRole(\App\Enums\UserRole::Client))
                                <a href="{{ $downloadRoute }}" class="btn btn-primary btn-sm">Download</a>
                            @elseif($downloadRoute)
                                <a href="{{ $downloadRoute }}" class="btn btn-primary btn-sm">Download</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($paginatedDeliveredReports->hasPages())
        {{ $paginatedDeliveredReports->links() }}
    @endif
    </div>
</section>
@endif
