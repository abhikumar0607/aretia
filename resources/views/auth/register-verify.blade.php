<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify email - Aretia</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="auth-body">
<div class="auth-page">
    <div class="auth-card auth-card--register">
        <div class="auth-logo-wrap">
            @include('partials.brand-logo', ['class' => 'site-logo-auth', 'width' => 220, 'height' => 56])
        </div>

        <div class="auth-head">
            <h1>Verify your email</h1>
            <p class="sub">We sent a 6-digit code to <strong>{{ $maskedEmail }}</strong></p>
        </div>

        <div id="toast-root" aria-live="polite"></div>
        @include('partials.alerts')

        <form method="POST" action="{{ route('register.verify.submit') }}" class="auth-form auth-form--register">
            @csrf
            <div class="auth-field auth-field--full">
                <label for="reg-otp">Verification code</label>
                <input
                    type="text"
                    id="reg-otp"
                    name="otp"
                    value="{{ old('otp') }}"
                    required
                    autofocus
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    placeholder="Enter 6-digit OTP"
                >
            </div>
            <button type="submit" class="btn btn-primary btn-lg auth-submit">Verify and continue to KYC</button>
        </form>

        <form method="POST" action="{{ route('register.resend-otp') }}" class="auth-form" style="margin-top: 0.75rem;">
            @csrf
            <button type="submit" class="btn btn-secondary btn-lg auth-submit">Resend code</button>
        </form>

        <p class="auth-footer">
            <a href="{{ route('register') }}">Start over</a>
            ·
            <a href="{{ route('login') }}">Already registered? Sign in</a>
        </p>
    </div>
</div>
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/ajax-submit.js') }}" defer></script>
</body>
</html>
