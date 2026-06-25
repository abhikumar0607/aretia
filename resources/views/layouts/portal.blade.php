<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Aretia')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body>
<div class="app-shell">
    @auth
        @php $roleValue = auth()->user()->role->value; @endphp
        @if($roleValue === 'superadmin')
            @include('partials.sidebar-superadmin')
        @elseif($roleValue === 'admin')
            @include('partials.sidebar-admin')
        @else
            @include('partials.sidebar')
        @endif
    @else
        @include('partials.sidebar')
    @endauth
    <div class="main-area">
        @include('partials.portal-header')
        <div class="main-gradient">
            <div class="page-container @yield('container_class')">
                @yield('content')
            </div>
        </div>
    </div>
</div>
<div id="toast-root" aria-live="polite"></div>
@include('partials.alerts')
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/due-date-inputs.js') }}" defer></script>
<script src="{{ asset('js/ajax-submit.js') }}" defer></script>
<script src="{{ asset('js/portal.js') }}" defer></script>
<script src="{{ asset('js/binary-upload.js') }}" defer></script>
@auth
@if(config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key'))
<script>
    window.AretiaBroadcast = {
        key: @json(config('broadcasting.connections.pusher.key')),
        cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
        authEndpoint: @json(url('/broadcasting/auth')),
        userId: @json(auth()->id()),
    };
</script>
<script src="https://js.pusher.com/8.4.0-rc2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
<script>
    window.EchoConstructor = window.Echo;
    window.getAretiaEcho = function (csrfToken) {
        if (window.__aretiaEcho) {
            return window.__aretiaEcho;
        }
        if (!window.AretiaBroadcast || !window.Pusher || !window.EchoConstructor) {
            return null;
        }
        window.Pusher = window.Pusher;
        window.__aretiaEcho = new window.EchoConstructor({
            broadcaster: 'pusher',
            key: window.AretiaBroadcast.key,
            cluster: window.AretiaBroadcast.cluster,
            forceTLS: true,
            authEndpoint: window.AretiaBroadcast.authEndpoint,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        });
        return window.__aretiaEcho;
    };
    window.createAretiaEcho = window.getAretiaEcho;
</script>
<script src="{{ asset('js/notifications.js') }}" defer></script>
@if(auth()->user()->hasAnyChatPermission())
<script src="{{ asset('js/chat-notifications.js') }}" defer></script>
@endif
@endif
@endauth
@stack('scripts')
</body>
</html>
