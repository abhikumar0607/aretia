@extends('layouts.portal')
@section('title', $case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $chatPartner = $case->chatPartnerFor(auth()->user());
@endphp
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => route('analyst.cases.index'),
    'backLabel' => 'My cases',
    'enableChat' => true,
    'chatLabel' => $chatPartner ? 'Chat with '.$chatPartner->name : 'Chat with client',
])

<div class="case-actions-grid">
    <section class="case-action-card card">
        <div class="case-panel-head">
            <h3>Update stage</h3>
        </div>
        <form method="POST" action="{{ route('analyst.cases.stage', $case) }}" class="case-action-form">
            @csrf
            <div class="form-field">
                <label for="workflow_stage_id">Current stage</label>
                <select name="workflow_stage_id" id="workflow_stage_id">
                    @foreach($stages as $stage)
                        <option value="{{ $stage->id }}" @selected($case->workflow_stage_id == $stage->id)>{{ $stage->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label for="stage_notes">Notes (optional)</label>
                <input type="text" name="notes" id="stage_notes" placeholder="Add a note for this stage change">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Update stage</button>
        </form>
    </section>

    <section class="case-action-card card case-action-card-accent">
        <div class="case-panel-head">
            <h3>Deliver report</h3>
            <span class="pill pill-package">Notify client</span>
        </div>
        <form method="POST" action="{{ route('analyst.reports.store', $case) }}" id="report-upload-form" class="case-action-form">
            @csrf
            <div class="form-field">
                <label for="report_title">Report title</label>
                <input type="text" name="title" id="report_title" placeholder="e.g. Due diligence report — Q1 2026" required>
            </div>
            <div class="form-field">
                <label>Report file (PDF/DOC)</label>
                <p class="form-field-hint">Max 5 MB</p>
                <div class="import-file-zone order-file-zone" data-dropzone>
                    <input type="file" id="report_file" accept=".pdf,.doc,.docx">
                    <div class="import-file-zone-inner">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span class="import-file-label">Drop report or <strong>browse</strong></span>
                        <span class="import-file-name" data-file-name></span>
                    </div>
                </div>
            </div>
            <div class="form-field case-checkbox-field">
                <label class="case-checkbox-label">
                    <input type="checkbox" name="is_password_protected" value="1" id="report_password_toggle">
                    Password protected file
                </label>
            </div>
            <div class="form-field" id="report_password_wrap" hidden>
                <label for="file_password">File password</label>
                <input type="text" name="file_password" id="file_password" placeholder="Password client will use to open file">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Upload &amp; notify client
            </button>
        </form>
    </section>
</div>

@include('partials.case-panel', ['case' => $case])
@include('partials.case-chat', ['case' => $case])
@endsection

@push('scripts')
<script src="{{ asset('js/case-chat.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('report_password_toggle');
    const wrap = document.getElementById('report_password_wrap');
    if (toggle && wrap) {
        toggle.addEventListener('change', () => {
            wrap.hidden = !toggle.checked;
        });
    }
});
</script>
@endpush
