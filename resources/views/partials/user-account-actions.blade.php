@if($canManage)
    <div class="cell-action-stack">
        @if(!empty($editRoute))
            <a href="{{ $editRoute }}" class="btn btn-secondary btn-sm">Edit</a>
        @endif

        @if(!empty($companySuspended))
            <span class="cell-muted">Restore company first</span>
        @elseif($user->is_active)
            <form method="POST" action="{{ $deactivateRoute }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">Remove access</button>
            </form>
        @else
            <form method="POST" action="{{ $activateRoute }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">Restore access</button>
            </form>
        @endif

        <form id="delete-account-form-{{ $user->id }}" method="POST" action="{{ $deleteRoute }}" data-no-toast hidden>
            @csrf
            @method('DELETE')
        </form>
        <button
            type="button"
            class="btn btn-danger btn-sm"
            data-delete-account-open
            data-delete-form="delete-account-form-{{ $user->id }}"
            data-user-name="{{ $user->name }}"
        >Delete account</button>
    </div>

    @once('delete-account-modal')
        @include('partials.delete-account-modal')
        @push('scripts')
            <script src="{{ asset('js/account-delete-modal.js') }}" defer></script>
        @endpush
    @endonce
@else
    <span class="cell-muted">—</span>
@endif
