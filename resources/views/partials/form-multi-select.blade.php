@php
    $inputName = str_ends_with($name ?? '', '[]') ? ($name ?? 'items[]') : (($name ?? 'items').'[]');
    $selected = array_map('intval', $selected ?? []);
    $placeholder = $placeholder ?? 'Select…';
    $min = $min ?? 1;
    $requiredMessage = $requiredMessage ?? 'Please select at least one option.';
@endphp
<div class="ms-wrap" data-multi-select data-min="{{ $min }}" data-required-message="{{ $requiredMessage }}">
    @if(!empty($label))
        <span class="form-label ms-label">{{ $label }}</span>
    @endif
    <div class="ms-control">
        <button type="button" class="ms-trigger" aria-haspopup="listbox" aria-expanded="false">
            <span class="ms-trigger-text" data-placeholder="{{ $placeholder }}">{{ $placeholder }}</span>
            <svg class="ms-chevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="ms-panel" role="listbox" hidden>
            @foreach($options as $option)
                @php $value = (int) $option['value']; @endphp
                <label class="ms-option">
                    <input
                        type="checkbox"
                        name="{{ $inputName }}"
                        value="{{ $value }}"
                        @checked(in_array($value, $selected, true))
                    >
                    <span>{{ $option['label'] }}</span>
                </label>
            @endforeach
        </div>
    </div>
    @if(!empty($hint))
        <p class="form-field-hint">{{ $hint }}</p>
    @endif
</div>
