@extends('layouts.portal')
@section('title', 'Add Employee')
@section('container_class', 'page-container-wide')

@section('content')
<a href="{{ route('superadmin.employees.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to employees
</a>

<header class="listing-hero import-hero">
    <div class="listing-hero-text">
        <h1>Add employee</h1>
        <p>Create a new team account and choose their role: Analyst, QA, or FQA.</p>
    </div>
</header>

<div class="order-form-panel card">
    <form method="POST" action="{{ route('superadmin.employees.store') }}" class="team-employee-form">
        @csrf
        @include('superadmin.employees._fields', ['employee' => null])
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create employee</button>
            <a href="{{ route('superadmin.employees.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

