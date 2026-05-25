<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password - Aretia</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="auth-body">
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            @include('partials.brand-logo', ['class' => 'site-logo-auth', 'width' => 220, 'height' => 56])
        </div>

        <div class="auth-head">
            <h1>Forgot password?</h1>
            <p class="sub">Enter your email and we'll send you a reset link.</p>
        </div>

        <div id="toast-root" aria-live="polite"></div>
        @include('partials.alerts')

        <form method="POST" action="{{ route('password.email') }}" id="forgot-form" class="auth-form">
            @csrf
            <div class="auth-field">
                <label for="forgot-email">Email address</label>
                <input type="email" id="forgot-email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </div>
            <button type="submit" class="btn btn-primary btn-lg auth-submit">Send reset link</button>
        </form>

        <p class="auth-footer">
            Remembered your password?
            <a href="{{ route('login') }}">Back to sign in</a>
        </p>
    </div>
</div>
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/ajax-submit.js') }}" defer></script>
</body>
</html>
