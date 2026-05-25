@extends('layouts.portal')
@section('title', 'Bulk Import Orders')
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.bulk-import', [
    'title' => 'Bulk import orders',
    'subtitle' => 'Upload Excel (.xlsx, .xls) or CSV with multiple orders at once.',
    'backRoute' => route('client.orders.index'),
    'backLabel' => 'Back to orders',
    'templateRoute' => route('client.orders.import.template'),
    'uploadRoute' => route('client.orders.import.store'),
    'step1Hint' => 'Fill the template with your orders, then upload in step 2.',
    'columns' => [
        ['name' => 'package_slug', 'required' => 'Yes', 'example' => 'basic-risk-spectrum, custom'],
        ['name' => 'due_date', 'required' => 'No', 'example' => '2026-06-15 (YYYY-MM-DD)'],
        ['name' => 'subject_type', 'required' => 'For non-custom', 'example' => 'individual or entity'],
        ['name' => 'subject_name', 'required' => 'For non-custom', 'example' => 'John Doe'],
        ['name' => 'subject_details', 'required' => 'No', 'example' => 'Extra notes'],
        ['name' => 'custom_request', 'required' => 'For custom package', 'example' => 'Your request text'],
    ],
    'packages' => $packages,
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
