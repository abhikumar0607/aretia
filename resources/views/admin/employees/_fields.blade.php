@php
    $isEdit = $employee !== null;
@endphp

<div class="form-field">
    <label for="employee-name">Full name</label>
    <input type="text" id="employee-name" name="name" value="{{ old('name', $employee?->name) }}" required autocomplete="name">
</div>
<div class="form-field">
    <label for="employee-email">Email</label>
    <input type="email" id="employee-email" name="email" value="{{ old('email', $employee?->email) }}" required autocomplete="email">
</div>
<div class="form-field">
    <label for="employee-phone">Phone <span class="form-optional">(optional)</span></label>
    <input type="number" id="employee-phone" name="phone" value="{{ old('phone', $employee?->phone) }}" inputmode="numeric" autocomplete="tel" min="0" step="1">
</div>
@include('partials.employee-role-select', [
    'id' => 'employee-role',
    'employee' => $employee,
])

@if(!$isEdit)
    <div class="form-field">
        <label for="employee-password">Password</label>
        <input type="password" id="employee-password" name="password" required autocomplete="new-password" aria-describedby="employee-password-hint">
        <p class="form-field-hint" id="employee-password-hint">{{ \App\Support\PasswordRules::hint() }}</p>
    </div>
    <div class="form-field">
        <label for="employee-password-confirm">Confirm password</label>
        <input type="password" id="employee-password-confirm" name="password_confirmation" required autocomplete="new-password">
    </div>
@endif
