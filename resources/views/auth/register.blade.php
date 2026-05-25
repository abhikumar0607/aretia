<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Aretia</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body class="auth-body">
<div class="auth-page">
    <div class="auth-card auth-card--register">
        <div class="auth-logo-wrap">
            @include('partials.brand-logo', ['class' => 'site-logo-auth', 'width' => 220, 'height' => 56])
        </div>

        <div class="auth-head">
            <h1>Create your account</h1>
            <p class="sub">Register your company to start due diligence onboarding</p>
        </div>

        <div id="toast-root" aria-live="polite"></div>
        @include('partials.alerts')

        <form method="POST" action="{{ route('register') }}" class="auth-form auth-form--register">
            @csrf
            <div class="auth-form-grid">
                <div class="auth-field">
                    <label for="reg-name">Full name</label>
                    <input type="text" id="reg-name" name="name" value="{{ old('name') }}" required autocomplete="name">
                </div>
                <div class="auth-field">
                    <label for="reg-company">Company name</label>
                    <input type="text" id="reg-company" name="company" value="{{ old('company') }}" required autocomplete="organization">
                </div>
                <div class="auth-field auth-field--full">
                    <label for="reg-email">Work email</label>
                    <input type="email" id="reg-email" name="email" value="{{ old('email') }}" required autocomplete="email">
                </div>
                <div class="auth-field auth-field--full">
                    <label for="reg-phone">Phone</label>
                    <input type="tel" id="reg-phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel">
                </div>
                <div class="auth-field">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" required autocomplete="new-password">
                </div>
                <div class="auth-field">
                    <label for="reg-password-confirm">Confirm password</label>
                    <input type="password" id="reg-password-confirm" name="password_confirmation" required autocomplete="new-password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg auth-submit">Continue to KYC</button>
        </form>

        <p class="auth-footer">
            <a href="{{ route('login') }}">Already registered? Sign in</a>
        </p>
    </div>
</div>
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/ajax-submit.js') }}" defer></script>
</body>
</html>
