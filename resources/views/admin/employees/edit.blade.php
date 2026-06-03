@extends('layouts.portal')
@section('title', 'Edit Employee')
@section('container_class', 'page-container-wide')

@section('content')
<a href="{{ route('admin.employees.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to employees
</a>

<header class="listing-hero import-hero">
    <div class="listing-hero-text">
        <h1>Edit employee</h1>
        <p>Update {{ $employee->name }}’s details and role.</p>
    </div>
</header>

<div class="order-form-panel card">
    <form method="POST" action="{{ route('admin.employees.update', $employee) }}" class="team-employee-form">
        @csrf
        @method('PATCH')
        @include('admin.employees._fields', ['employee' => $employee])
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('admin.employees.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
