@extends('layouts.portal')
@section('title', 'New Order')
@section('container_class', 'page-container-wide')

@section('content')
<a href="{{ route('client.orders.index') }}" class="back-link">
    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to orders
</a>

<header class="listing-hero import-hero">
    <div class="listing-hero-text">
        <h1>Place an order</h1>
        <p>Select a service package and enter subject details. Supporting files are saved securely on the server.</p>
    </div>
</header>

<div class="order-form-panel card">
    <form method="POST" action="{{ route('client.orders.store') }}" enctype="multipart/form-data" class="order-form" id="order-form">
        @csrf

        <div class="order-form-section">
            <h3 class="order-form-section-title">Service package</h3>
            <div class="form-field">
                <label for="package-select">Package</label>
                <select name="service_package_id" id="package-select" required onchange="toggleCustom()">
                    @foreach($packages as $package)
                        <option value="{{ $package->id }}" data-custom="{{ $package->is_custom ? '1' : '0' }}"
                            data-days="{{ $package->due_days ?? '' }}"
                            {{ ($selected && $selected->id === $package->id) ? 'selected' : '' }}>
                            {{ $package->name }}{{ $package->due_days ? ' — '.$package->due_days.' days' : '' }}
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
                <textarea name="subject_details" id="subject_details" placeholder="Additional information about the subject (address, registration, identifiers…)">{{ old('subject_details') }}</textarea>
            </div>

            <div class="form-field">
                <label>Supporting documents</label>
                <p class="form-field-hint">PDF, Word, or images — max 5 MB each, up to 5 files.</p>
                <div class="import-file-zone order-file-zone" data-dropzone>
                    <input type="file" id="order_documents" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <div class="import-file-zone-inner">
                        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span class="import-file-label">Drop files here or <strong>browse</strong></span>
                        <span class="import-file-name" data-file-name></span>
                    </div>
                </div>
            </div>
        </div>

        <div id="custom-fields" class="order-form-section order-form-custom" style="display:none;">
            <h3 class="order-form-section-title">Custom request</h3>
            <div class="form-field">
                <label for="custom_request">Describe your requirements</label>
                <textarea name="custom_request" id="custom_request" class="order-custom-textarea" placeholder="Describe your due diligence requirements in detail…">{{ old('custom_request') }}</textarea>
            </div>
        </div>

        <div class="order-form-footer">
            <p class="form-hint">By confirming, you agree the information provided is accurate.</p>
            <button type="submit" class="btn btn-primary btn-lg order-submit-btn">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Confirm order
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleCustom() {
    const sel = document.getElementById('package-select');
    const opt = sel.options[sel.selectedIndex];
    const isCustom = opt.dataset.custom === '1';
    document.getElementById('standard-fields').style.display = isCustom ? 'none' : 'block';
    document.getElementById('custom-fields').style.display = isCustom ? 'block' : 'none';
}
toggleCustom();
</script>
@endpush
