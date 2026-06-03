@php
    $fieldId = $id ?? 'employee-type';
    $value = $selected ?? old('employee_type', 'analyst');
@endphp
<div class="form-field">
    <label for="{{ $fieldId }}">Role</label>
    <select id="{{ $fieldId }}" name="employee_type" required>
        @foreach(\App\Enums\EmployeeType::options() as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($value === $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <p class="form-field-hint">Analyst, QA, and FQA each have their own case workflow permissions.</p>
</div>
