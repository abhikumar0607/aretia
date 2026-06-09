<label class="perm-matrix-check">
    <input
        type="checkbox"
        @if(!empty($name)) name="{{ $name }}" value="1" @endif
        @checked($checked ?? false)
        @disabled($disabled ?? false)
    >
    <span class="perm-matrix-check-box" aria-hidden="true"></span>
    <span class="perm-matrix-check-label">{{ ($checked ?? false) ? 'Yes' : 'No' }}</span>
</label>
