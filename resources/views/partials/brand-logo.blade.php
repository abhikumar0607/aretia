@php
    $logoUrl = asset('images/aretia-logo.png');
    $logoAlt = $alt ?? 'Aretia';
    $logoClass = trim('site-logo '.($class ?? ''));
    $logoLink = $link ?? null;
@endphp
@if($logoLink)
    <a href="{{ $logoLink }}" class="site-logo-link {{ $linkClass ?? '' }}" aria-label="{{ $logoAlt }}">
        <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="{{ $logoClass }}" width="{{ $width ?? 180 }}" height="{{ $height ?? 48 }}" decoding="async">
    </a>
@else
    <img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" class="{{ $logoClass }}" width="{{ $width ?? 180 }}" height="{{ $height ?? 48 }}" decoding="async">
@endif
