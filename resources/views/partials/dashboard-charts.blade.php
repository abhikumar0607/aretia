@if(!empty($charts))
<section class="dashboard-section dashboard-charts-section" aria-label="Dashboard charts">
    <div class="dashboard-section-title">
        <h2>Overview</h2>
        <p>Live breakdown of your data</p>
    </div>

    <div class="dashboard-charts-grid">
        @foreach($charts as $chart)
            @php
                $total = array_sum($chart['values']);
            @endphp
            <article class="dashboard-chart-card">
                <header class="dashboard-chart-card-head">
                    <h3 class="dashboard-chart-title">{{ $chart['title'] }}</h3>
                    @if($total > 0)
                        <span class="dashboard-chart-badge">{{ $total }} total</span>
                    @endif
                </header>

                @if($total > 0)
                    <div class="dashboard-chart-body">
                        <div class="dashboard-chart-visual">
                            <div class="dashboard-chart-canvas-wrap">
                                <canvas id="{{ $chart['id'] }}"
                                    aria-label="{{ $chart['title'] }}"
                                    data-labels="{{ json_encode($chart['labels']) }}"
                                    data-values="{{ json_encode($chart['values']) }}"
                                    data-colors="{{ json_encode($chart['colors']) }}"
                                    data-total="{{ $total }}"></canvas>
                            </div>
                            <div class="dashboard-chart-center" aria-hidden="true">
                                <span class="dashboard-chart-center-value">{{ $total }}</span>
                                <span class="dashboard-chart-center-label">Total</span>
                            </div>
                        </div>

                        <ul class="dashboard-chart-breakdown">
                            @foreach($chart['labels'] as $i => $label)
                                @php
                                    $value = $chart['values'][$i];
                                    $pct = $total > 0 ? round(($value / $total) * 100) : 0;
                                    $color = $chart['colors'][$i] ?? '#94a3b8';
                                @endphp
                                <li>
                                    <span class="dashboard-chart-dot" style="background: {{ $color }}"></span>
                                    <span class="dashboard-chart-breakdown-label">{{ $label }}</span>
                                    <span class="dashboard-chart-breakdown-meta">{{ $value }} · {{ $pct }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="dashboard-chart-empty-wrap">
                        <svg width="36" height="36" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                        <p class="dashboard-chart-empty">No data yet</p>
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
