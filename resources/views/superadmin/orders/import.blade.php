@extends('layouts.portal')
@section('title', 'Bulk Import Orders')
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.bulk-import', [
    'title' => 'Bulk import orders',
    'subtitle' => 'Import orders for any client company using Excel or CSV.',
    'backRoute' => route('superadmin.orders.index'),
    'backLabel' => 'Back to orders',
    'templateRoute' => route('superadmin.orders.import.template'),
    'uploadRoute' => route('superadmin.orders.import.store'),
    'step1Hint' => 'Super Admin template includes company_name to assign each row to a client.',
    'columns' => [
        ['name' => 'company_name', 'required' => 'Yes', 'example' => 'Acme Corp'],
        ['name' => 'package_slug', 'required' => 'Yes', 'example' => 'standard-risk-spectrum'],
        ['name' => 'due_date', 'required' => 'No', 'example' => '2026-06-15 (YYYY-MM-DD)'],
        ['name' => 'subject_type', 'required' => 'For non-custom', 'example' => 'individual / entity'],
        ['name' => 'subject_name', 'required' => 'For non-custom', 'example' => 'Subject name'],
        ['name' => 'subject_details', 'required' => 'No', 'example' => 'Notes'],
        ['name' => 'custom_request', 'required' => 'For custom', 'example' => 'Custom order text'],
    ],
    'packages' => $packages,
    'packagesTitle' => 'Package slugs',
])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const zone = document.querySelector('.import-file-zone[data-dropzone]');
    if (!zone) return;
    const input = zone.querySelector('input[type="file"]');
    const nameEl = zone.querySelector('[data-file-name]');
    const show = (file) => {
        if (file && nameEl) {
            nameEl.textContent = file.name;
            zone.classList.add('has-file');
        }
    };
    input?.addEventListener('change', () => input.files?.[0] && show(input.files[0]));
    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        if (e.dataTransfer.files?.[0]) {
            const dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            input.files = dt.files;
            show(e.dataTransfer.files[0]);
        }
    });
});
</script>
@endpush

