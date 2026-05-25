<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aretia</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="auth-body">
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            @include('partials.brand-logo', ['class' => 'site-logo-auth', 'width' => 220, 'height' => 56])
        </div>

        <div class="auth-head">
            <h1>Welcome back</h1>
            <p class="sub">Sign in to your due diligence portal</p>
        </div>

        <div id="toast-root" aria-live="polite"></div>
        @include('partials.alerts')

        <form method="POST" action="{{ route('login') }}" id="login-form" class="auth-form">
            @csrf
            <div class="auth-field">
                <label for="login-email">Email address</label>
                <input type="email" id="login-email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </div>
            <div class="auth-field">
                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="password" required autocomplete="current-password">
            </div>
            <label class="auth-remember">
                <input type="checkbox" name="remember">
                <span>Remember me</span>
            </label>
            <button type="submit" class="btn btn-primary btn-lg auth-submit">Sign in</button>
        </form>

        <p class="auth-footer">
            New client?
            <a href="{{ route('register') }}">Create account</a>
        </p>
    </div>
</div>
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/ajax-submit.js') }}" defer></script>
</body>
</html>
