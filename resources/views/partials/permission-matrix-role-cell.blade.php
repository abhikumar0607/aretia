@if($editable ?? false)
    @include('partials.permission-matrix-checkbox', [
        'name' => $name ?? null,
        'checked' => $checked ?? false,
        'disabled' => $disabled ?? false,
    ])
@else
    <span class="perm-no">No</span>
@endif
