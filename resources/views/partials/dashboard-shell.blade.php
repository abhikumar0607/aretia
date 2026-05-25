<div class="dashboard-page">
    <header class="dashboard-header">
        <div class="dashboard-header-text">
            <h1>{{ $heading }}</h1>
            @if(!empty($description))
                <p>{{ $description }}</p>
            @endif
        </div>
    </header>

    @if(!empty($alert))
        <div class="alert alert-info dashboard-alert">{!! $alert !!}</div>
    @endif

    @if(!empty($statCards))
        <section class="dashboard-metrics" aria-label="Key metrics">
            @foreach($statCards as $card)
                <div class="dashboard-metric {{ !empty($card['accent']) ? 'dashboard-metric-accent' : '' }} {{ !empty($card['warn']) ? 'dashboard-metric-warn' : '' }}">
                    <span class="dashboard-metric-label">{{ $card['label'] }}</span>
                    <span class="dashboard-metric-value">{{ $card['value'] }}</span>
                </div>
            @endforeach
        </section>
    @endif

    @include('partials.dashboard-charts', ['charts' => $charts ?? []])

    @if(!empty($quickLinks))
        <section class="dashboard-section">
            <div class="dashboard-section-title">
                <h2>Quick actions</h2>
            </div>
            <div class="dashboard-actions-grid">
                @foreach($quickLinks as $link)
                    <a href="{{ route($link['route']) }}" class="dashboard-action-card">
                        <h3>{{ $link['title'] }}</h3>
                        <p>{{ $link['text'] }}</p>
                        <span class="dashboard-action-arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
