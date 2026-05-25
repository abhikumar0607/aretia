@php
    $client = $case->resolvedClient();
@endphp
@if($client)
    <div class="cell-client">
        <span class="cell-client-name">{{ $client->name }}</span>
        @if($client->email)
            <span class="cell-sub">{{ $client->email }}</span>
        @endif
    </div>
@else
    <span class="cell-muted">—</span>
@endif
