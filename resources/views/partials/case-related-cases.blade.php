@php
    $relatedCases = $relatedCases ?? collect();
@endphp

@if($relatedCases->isNotEmpty())
<section class="card case-related-panel" style="margin-top:1.25rem;">
    <div class="case-related-head">
        <div class="case-related-head-text">
            <div class="case-related-head-icon" aria-hidden="true">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
            </div>
            <div>
                <h3>Related cases</h3>
                <p class="case-related-desc">Grouped as one related matter — click a reference to open the case.</p>
            </div>
        </div>
        <span class="pill pill-package">{{ $relatedCases->count() }} linked</span>
    </div>

    <div class="data-table-wrap case-related-table-wrap">
        <table class="data-table case-related-table">
            <thead>
                <tr>
                    <th>Case reference</th>
                    <th>Package</th>
                    <th>Confirmed</th>
                    <th>Due date</th>
                    <th>Stage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($relatedCases as $related)
                    <tr>
                        <td>
                            <a href="{{ \App\Support\PortalRoute::route('cases.show', $related) }}" class="row-link">
                                <span class="cell-ref">{{ $related->reference }}</span>
                            </a>
                        </td>
                        <td><span class="pill pill-package">{{ $related->order->package->name }}</span></td>
                        <td>
                            <span class="cell-date">
                                {{ $related->order->confirmed_at?->format('d M Y') ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="cell-date">{{ $related->portalDueDateLabel() }}</span>
                        </td>
                        <td>
                            @if($related->stage)
                                <span class="stage-pill" style="--stage-color: {{ $related->visibleStageColor() }}">{{ $related->visibleStageLabel() }}</span>
                            @else
                                <span class="cell-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
