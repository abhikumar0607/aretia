@extends('layouts.portal')
@section('title', 'New Order')
@section('container_class', 'page-container-wide')

@section('content')
<a href="{{ route('admin.orders.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to orders
</a>

<header class="listing-hero import-hero">
    <div class="listing-hero-text">
        <h1>Create order</h1>
        <p>Place an order on behalf of a client company. The case is created immediately — no approval step.</p>
    </div>
</header>

<div class="order-form-panel card">
    <form method="POST" action="{{ route('admin.orders.store') }}" class="order-form" id="order-form">
        @csrf

        <div class="order-form-section">
            <h3 class="order-form-section-title">Client company</h3>
            <div class="form-field">
                <label for="company_id">Company</label>
                <select name="company_id" id="company_id" required>
                    <option value="">Select company…</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                            {{ $company->name }} ({{ $company->email }})
                        </option>
                    @endforeach
                </select>
                @if($companies->isEmpty())
                    <p class="form-field-hint" style="color:var(--danger);">No active companies. Approve onboarding first.</p>
                @endif
            </div>
        </div>

        <div class="order-form-section">
            <h3 class="order-form-section-title">Service package</h3>
            <div class="form-field">
                <label for="package-select">Package</label>
                <select name="service_package_id" id="package-select" required onchange="toggleCustom()">
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}" data-custom="{{ $package->is_custom ? '1' : '0' }}"
                            {{ ($selected && $selected->id === $package->id) ? 'selected' : '' }}>
                            {{ $package->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="standard-fields" class="order-form-section">
            <h3 class="order-form-section-title">Subject information</h3>
            <div class="order-form-grid">
                <div class="form-field">
                    <label for="subject_type">Subject type</label>
                    <select name="subject_type" id="subject_type">
                        <option value="individual" @selected(old('subject_type') === 'individual')>Individual</option>
                        <option value="entity" @selected(old('subject_type') === 'entity')>Entity</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="subject_name">Subject name</label>
                    <input type="text" name="subject_name" id="subject_name" value="{{ old('subject_name') }}" placeholder="Full legal name">
                </div>
            </div>
            <div class="form-field">
                <label for="subject_details">Subject details</label>
                <textarea name="subject_details" id="subject_details" placeholder="Additional information…">{{ old('subject_details') }}</textarea>
            </div>
        </div>

        <div id="custom-fields" class="order-form-section order-form-custom" style="display:none;">
            <h3 class="order-form-section-title">Custom order</h3>
            <div class="order-form-grid">
                <div class="form-field">
                    <label for="custom_subject_type">Subject type</label>
                    <select name="subject_type" id="custom_subject_type">
                        <option value="individual" @selected(old('subject_type') === 'individual')>Individual</option>
                        <option value="entity" @selected(old('subject_type') === 'entity')>Entity</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="custom_subject_name">Subject name <span class="form-optional">(optional)</span></label>
                    <input type="text" name="subject_name" id="custom_subject_name" value="{{ old('subject_name') }}" placeholder="Name or entity being investigated">
                </div>
            </div>
            <div class="form-field">
                <label for="custom_request">Describe requirements</label>
                <textarea name="custom_request" id="custom_request" class="order-custom-textarea" placeholder="Describe due diligence requirements…">{{ old('custom_request') }}</textarea>
            </div>
            <div class="form-field">
                <label for="custom_subject_details">Additional details <span class="form-optional">(optional)</span></label>
                <textarea name="subject_details" id="custom_subject_details" placeholder="Identifiers, jurisdiction, scope notes…">{{ old('subject_details') }}</textarea>
            </div>
        </div>

        <div class="order-form-section" id="order-documents-section">
            <h3 class="order-form-section-title">Supporting documents</h3>
            @include('partials.order-documents-field')
        </div>

        <div class="order-form-section">
            <h3 class="order-form-section-title">Due date</h3>
            <div class="form-field">
                <label for="due_date">Due date <span class="form-optional">(optional)</span></label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date') }}">
            </div>
        </div>

        <div class="order-form-footer">
            <button type="submit" class="btn btn-primary btn-lg order-submit-btn" @disabled($companies->isEmpty())>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Create order & open case
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@include('partials.order-form-toggle-script')
@endpush
