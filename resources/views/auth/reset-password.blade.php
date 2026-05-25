<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password - Aretia</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="auth-body">
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            @include('partials.brand-logo', ['class' => 'site-logo-auth', 'width' => 220, 'height' => 56])
        </div>

        <div class="auth-head">
            <h1>Set a new password</h1>
            <p class="sub">Choose a strong password to secure your account.</p>
        </div>

        <div id="toast-root" aria-live="polite"></div>
        @include('partials.alerts')

        <form method="POST" action="{{ route('password.update') }}" id="reset-form" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="auth-field">
                <label for="reset-email">Email address</label>
                <input type="email" id="reset-email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
            </div>
            <div class="auth-field">
                <label for="reset-password">New password</label>
                <input type="password" id="reset-password" name="password" required autocomplete="new-password" minlength="8">
            </div>
            <div class="auth-field">
                <label for="reset-password-confirm">Confirm new password</label>
                <input type="password" id="reset-password-confirm" name="password_confirmation" required autocomplete="new-password" minlength="8">
            </div>
            <button type="submit" class="btn btn-primary btn-lg auth-submit">Reset password</button>
        </form>

        <p class="auth-footer">
            <a href="{{ route('login') }}">Back to sign in</a>
        </p>
    </div>
</div>
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/ajax-submit.js') }}" defer></script>
</body>
</html>
