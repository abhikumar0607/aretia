<div class="dashboard-recent-panel">
    <div class="dashboard-section-head">
        <h2>Recent cases</h2>
        <p class="dashboard-section-sub">Matches your filter selection</p>
    </div>
    <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Confirmed</th>
                    <th>Due date</th>
                    <th>Stage</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr class="data-table-row">
                    <td>
                        <a href="{{ \App\Support\PortalRoute::route('cases.show', $case) }}" class="row-link">
                            <strong>{{ $case->reference }}</strong>
                        </a>
                    </td>
                    <td>{{ $case->company->name }}</td>
                    <td>@include('partials.case-client-cell', ['case' => $case])</td>
                    <td><span class="cell-date">{{ $case->order?->confirmed_at?->format('d M Y') ?? '—' }}</span></td>
                    <td><span class="cell-date">{{ $case->order?->due_date?->format('d M Y') ?? 'TBD' }}</span></td>
                    <td>
                        @if($case->stage)
                            <span class="stage-pill" style="--stage-color: {{ $case->stage->color }}">{{ $case->stage->name }}</span>
                        @else
                            <span class="cell-muted">—</span>
                        @endif
                    </td>
                    <td><a href="{{ \App\Support\PortalRoute::route('cases.show', $case) }}" class="btn btn-secondary btn-sm">Open</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="case-empty-hint" style="text-align:center;padding:2rem;">No cases in this view.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
