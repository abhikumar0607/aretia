@extends('layouts.portal')
@section('title', 'Permissions & Roles')
@section('container_class', 'page-container-wide')

@section('content')
<header class="listing-hero">
    <div class="listing-hero-text">
        <h1>Permissions &amp; Roles</h1>
    </div>
</header>

<div class="perm-matrix-card card">
    <form method="POST" action="{{ route('superadmin.roles.update') }}" class="perm-matrix-form">
        @csrf

        <div class="perm-matrix-scroll">
            <table class="perm-matrix-table">
                <thead>
                    <tr>
                        <th class="perm-matrix-th perm-matrix-th--section">Section</th>
                        <th class="perm-matrix-th perm-matrix-th--permission">Permission</th>
                        @foreach($displayRoles as $role)
                            <th class="perm-matrix-th perm-matrix-th--role @if($role->value === 'admin') perm-matrix-th--admin @elseif($role->value === 'superadmin') perm-matrix-th--super @endif">
                                {{ $role->label() }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedPermissions as $section => $permissions)
                        @foreach($permissions as $index => $permission)
                            <tr class="perm-matrix-row">
                                @if($index === 0)
                                    <td class="perm-matrix-section" rowspan="{{ count($permissions) }}">
                                        {{ $section }}
                                    </td>
                                @endif
                                <td class="perm-matrix-permission">
                                    <strong>{{ $permission->label() }}</strong>
                                    <span class="perm-matrix-permission-desc">{{ $permission->description() }}</span>
                                </td>
                                @foreach($displayRoles as $role)
                                    @php
                                        $isStaff = in_array($role, $staffRoles, true);
                                        $checked = $grants[$permission->value][$role->value] ?? false;
                                        $editable = $permission->isUniversal() || $isStaff;
                                    @endphp
                                    <td class="perm-matrix-cell @if($role->value === 'admin') perm-matrix-cell--admin @elseif($role->value === 'superadmin') perm-matrix-cell--super @endif">
                                        @include('partials.permission-matrix-role-cell', [
                                            'editable' => $editable,
                                            'name' => $editable ? 'permissions['.$permission->value.']['.$role->value.']' : null,
                                            'checked' => $editable ? $checked : false,
                                        ])
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="perm-matrix-footer">
            <button type="submit" class="btn btn-primary">Save permissions</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.perm-matrix-check input[type="checkbox"]').forEach((input) => {
        const label = input.closest('.perm-matrix-check')?.querySelector('.perm-matrix-check-label');
        const sync = () => {
            if (label) {
                label.textContent = input.checked ? 'Yes' : 'No';
                label.className = 'perm-matrix-check-label ' + (input.checked ? 'perm-yes' : 'perm-no');
            }
        };
        input.addEventListener('change', sync);
        sync();
    });
});
</script>
@endpush
