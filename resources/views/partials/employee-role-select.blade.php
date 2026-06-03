@php
    $fieldId = $id ?? 'employee-role';
    $value = $selected ?? old('role', $employee?->role?->value ?? 'analyst');
@endphp
<div class="form-field">
    <label for="{{ $fieldId }}">Role</label>
    <select id="{{ $fieldId }}" name="role" required>
        @foreach(\App\Enums\UserRole::employeeRoleOptions() as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <p class="form-field-hint">Analyst, QA, and FQA are separate accounts with their own login URLs.</p>
</div>
