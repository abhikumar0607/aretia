@extends('layouts.portal')
@section('title', 'My Profile')
@section('container_class', 'page-container-wide')

@section('content')
<div class="dashboard-page">
    <header class="dashboard-header">
        <div class="dashboard-header-text">
            <h1>My Profile</h1>
            <p>Update your name and profile photo.</p>
        </div>
    </header>

    <div class="profile-card card">
        <form method="POST" action="{{ route('profile.update') }}" id="profile-form" data-profile-form class="profile-form">
            @csrf

            <div class="profile-avatar-section">
                <div class="profile-avatar-preview" id="profile-avatar-preview">
                    @if($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" id="profile-avatar-img">
                    @else
                        <span class="profile-avatar-initials" id="profile-avatar-initials">{{ $user->initials() }}</span>
                    @endif
                </div>
                <div class="profile-avatar-fields">
                    <label class="profile-upload-label">
                        <span class="btn btn-secondary btn-sm">Choose photo</span>
                        <input type="file" id="profile-avatar-input" accept="image/jpeg,image/png,image/webp" hidden>
                    </label>
                    <p class="form-field-hint">JPG, PNG or WebP. Max 5 MB.</p>
                    @if($user->avatar_path)
                        <label class="profile-remove-avatar">
                            <input type="checkbox" name="remove_avatar" value="1">
                            Remove current photo
                        </label>
                    @endif
                </div>
            </div>

            <div class="profile-fields-grid">
                <div class="form-field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-field">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="form-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="{{ $user->email }}" disabled>
                    <p class="form-field-hint">Email cannot be changed here.</p>
                </div>
                <div class="form-field">
                    <label>Role</label>
                    <input type="text" value="{{ $user->role->label() }}" disabled>
                </div>
            </div>

            <details class="profile-password-details">
                <summary>Change password (optional)</summary>
                <div class="profile-fields-grid profile-fields-grid-2">
                    <div class="form-field">
                        <label for="password">New password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password">
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>
            </details>

            <div class="profile-form-actions">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route($user->role->dashboardRoute()) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

