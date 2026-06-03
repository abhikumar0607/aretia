@php
    $client = $case->resolvedClient();
    $viewer = auth()->user();
    $showClientEmail = $viewer && (
        $viewer->hasRole(\App\Enums\UserRole::Admin)
        || $viewer->hasRole(\App\Enums\UserRole::SuperAdmin)
        || $viewer->hasRole(\App\Enums\UserRole::Client)
    );
@endphp
@if($client)
    <div class="cell-client">
        <span class="cell-client-name">{{ $client->name }}</span>
        @if($showClientEmail && $client->email)
            <span class="cell-sub">{{ $client->email }}</span>
        @endif
    </div>
@else
    <span class="cell-muted">—</span>
@endif
