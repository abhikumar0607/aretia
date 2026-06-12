@if(!empty($charts))
@php
    $casesIndexRoute = auth()->check() ? \App\Support\PortalRoute::route('cases.index') : null;
    $ordersIndexRoute = auth()->check() ? \App\Support\PortalRoute::route('orders.index') : null;
    $chartIcons = [
        'onboarding' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'orders' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'cases-stage' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7',
        'reports' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'cases-status' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'users-role' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    ];
@endphp
<section class="dashboard-section dashboard-charts-section" aria-label="Dashboard charts">
    <div class="dashboard-section-title">
        <h2>Analytics</h2>
        <p>Filtered by your date range above</p>
    </div>

    @php
        $chartGridClass = count($charts) <= 2 ? 'dashboard-charts-grid--pair' : 'dashboard-charts-grid--quad';
    @endphp
    <div class="dashboard-charts-grid {{ $chartGridClass }}">
        @foreach($charts as $chart)
            @php
                $total = array_sum($chart['values']);
                $canvasValues = $chart['canvas_values'] ?? array_values(array_filter($chart['values'], fn ($v) => $v > 0));
                $canvasLabels = $chart['canvas_labels'] ?? [];
                $canvasColors = $chart['canvas_colors'] ?? [];
                if ($canvasLabels === [] && $canvasValues !== []) {
                    foreach ($chart['labels'] as $i => $label) {
                        if (($chart['values'][$i] ?? 0) > 0) {
                            $canvasLabels[] = $label;
                            $canvasColors[] = $chart['colors'][$i] ?? '#94a3b8';
                        }
                    }
                }
                $hasChartData = array_sum($canvasValues) > 0 || !empty($chart['show_all_slices']);
                $showAllSlices = !empty($chart['show_all_slices']);
                $layout = $chart['layout'] ?? 'ring';
                $variant = $chart['variant'] ?? 'default';
                $iconPath = $chartIcons[$chart['key'] ?? ''] ?? 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z';
            @endphp
            <article class="dashboard-chart-card dashboard-chart-card--{{ $variant }} dashboard-chart-card--{{ $layout }}">
                <header class="dashboard-chart-card-head">
                    <div class="dashboard-chart-card-brand">
                        <span class="dashboard-chart-card-icon" aria-hidden="true">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="dashboard-chart-title">{{ $chart['title'] }}</h3>
                            @if(!empty($chart['subtitle']))
                                <p class="dashboard-chart-subtitle">{{ $chart['subtitle'] }}</p>
                            @endif
                        </div>
                    </div>
                    @if($hasChartData)
                        <span class="dashboard-chart-badge">{{ $total }} total</span>
                    @endif
                </header>

                @if($hasChartData)
                    <div class="dashboard-chart-body dashboard-chart-body--{{ $layout }} @if($layout === 'ring') dashboard-chart-body--ring @endif">
                        @if(array_sum($canvasValues) > 0)
                        <div class="dashboard-chart-visual dashboard-chart-visual--{{ $layout }}">
                            <div class="dashboard-chart-canvas-wrap">
                                @php
                                    $canvasStageLinks = [];
                                    if (($chart['key'] ?? '') === 'cases-stage' && $casesIndexRoute) {
                                        foreach ($chart['labels'] as $sliceIndex => $sliceLabel) {
                                            if (($chart['values'][$sliceIndex] ?? 0) < 1) {
                                                continue;
                                            }
                                            $stageFilter = $chart['client_stage_slugs'][$sliceIndex]
                                                ?? $chart['stage_ids'][$sliceIndex]
                                                ?? null;
                                            if ($stageFilter) {
                                                $canvasStageLinks[] = route(
                                                    \App\Support\PortalRoute::name('cases.index'),
                                                    ($dashboardFilters ?? new \App\Support\DashboardFilters())->toCasesListingQueryArray($stageFilter)
                                                );
                                            }
                                        }
                                    }
                                    $canvasOrderLinks = [];
                                    if (($chart['key'] ?? '') === 'orders' && $ordersIndexRoute) {
                                        foreach ($chart['labels'] as $sliceIndex => $sliceLabel) {
                                            if (($chart['values'][$sliceIndex] ?? 0) < 1) {
                                                continue;
                                            }
                                            $orderStatus = $chart['order_statuses'][$sliceIndex] ?? null;
                                            if ($orderStatus) {
                                                $canvasOrderLinks[] = route(
                                                    \App\Support\PortalRoute::name('orders.index'),
                                                    ($dashboardFilters ?? new \App\Support\DashboardFilters())->toOrdersListingQueryArray($orderStatus)
                                                );
                                            }
                                        }
                                    }
                                @endphp
                                <canvas id="{{ $chart['id'] }}"
                                    aria-label="{{ $chart['title'] }}"
                                    data-chart-type="{{ $chart['type'] ?? 'doughnut' }}"
                                    data-chart-variant="{{ $variant }}"
                                    data-chart-layout="{{ $layout }}"
                                    @if(!empty($chart['horizontal'])) data-chart-horizontal="1" @endif
                                    @if(($chart['key'] ?? '') === 'cases-stage' && $canvasStageLinks !== []) data-stage-links="{{ json_encode($canvasStageLinks) }}" @endif
                                    @if(($chart['key'] ?? '') === 'orders' && $canvasOrderLinks !== []) data-order-links="{{ json_encode($canvasOrderLinks) }}" @endif
                                    data-labels="{{ json_encode($canvasLabels) }}"
                                    data-values="{{ json_encode($canvasValues) }}"
                                    data-colors="{{ json_encode($canvasColors) }}"
                                    data-total="{{ array_sum($canvasValues) }}"></canvas>
                            </div>
                            @if($layout === 'ring')
                                <div class="dashboard-chart-center" aria-hidden="true">
                                    <span class="dashboard-chart-center-value">{{ $total }}</span>
                                    <span class="dashboard-chart-center-label">Total</span>
                                </div>
                            @endif
                        </div>
                        @endif

                        <ul class="dashboard-chart-breakdown dashboard-chart-breakdown--{{ $layout }}">
                            @foreach($chart['labels'] as $i => $label)
                                @php
                                    $value = $chart['values'][$i];
                                    if (! $showAllSlices && $value < 1) {
                                        continue;
                                    }
                                    $pct = $total > 0 ? round(($value / $total) * 100) : 0;
                                    $color = $chart['colors'][$i] ?? '#94a3b8';
                                    $stageFilter = $chart['client_stage_slugs'][$i] ?? $chart['stage_ids'][$i] ?? null;
                                    $stageCasesUrl = null;
                                    if (($chart['key'] ?? '') === 'cases-stage' && $stageFilter && $casesIndexRoute) {
                                        $stageCasesUrl = route(
                                            \App\Support\PortalRoute::name('cases.index'),
                                            ($dashboardFilters ?? new \App\Support\DashboardFilters())->toCasesListingQueryArray($stageFilter)
                                        );
                                    }
                                    $orderStatus = $chart['order_statuses'][$i] ?? null;
                                    $statusOrdersUrl = null;
                                    if (($chart['key'] ?? '') === 'orders' && $orderStatus && $ordersIndexRoute) {
                                        $statusOrdersUrl = route(
                                            \App\Support\PortalRoute::name('orders.index'),
                                            ($dashboardFilters ?? new \App\Support\DashboardFilters())->toOrdersListingQueryArray($orderStatus)
                                        );
                                    }
                                    $breakdownUrl = $stageCasesUrl ?? $statusOrdersUrl;
                                    $breakdownAria = $stageCasesUrl
                                        ? "View {$label} cases in a new tab"
                                        : ($statusOrdersUrl ? "View {$label} orders in a new tab" : null);
                                @endphp
                                <li
                                    @if($breakdownUrl)
                                        class="dashboard-chart-breakdown-item--linked"
                                        @if($stageCasesUrl) data-cases-url="{{ $stageCasesUrl }}" @endif
                                        @if($statusOrdersUrl) data-orders-url="{{ $statusOrdersUrl }}" @endif
                                        role="link"
                                        tabindex="0"
                                        aria-label="{{ $breakdownAria }}"
                                    @endif
                                >
                                    <span class="dashboard-chart-dot" style="background: {{ $color }}"></span>
                                    <span class="dashboard-chart-breakdown-label">{{ $label }}</span>
                                    @if(in_array($layout, ['bars', 'bars-h'], true))
                                        <span class="dashboard-chart-bar-track" aria-hidden="true">
                                            <span class="dashboard-chart-bar-fill" style="width: {{ $pct }}%; background: {{ $color }}"></span>
                                        </span>
                                    @endif
                                    <span class="dashboard-chart-breakdown-meta">{{ $value }}@if($total > 0) · {{ $pct }}%@endif</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="dashboard-chart-empty-wrap">
                        <div class="dashboard-chart-empty-icon" aria-hidden="true">
                            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $iconPath }}"/>
                            </svg>
                        </div>
                        <p class="dashboard-chart-empty">No data for this view</p>
                        <p class="dashboard-chart-empty-hint">Try widening the date range above or reset filters</p>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard-charts.js') }}"></script>
@endpush
@endif
