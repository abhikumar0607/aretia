@extends('layouts.portal')
@section('title', $case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $chatPartner = $case->chatPartnerFor(auth()->user());
@endphp
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => \App\Support\PortalRoute::route('cases.index'),
    'backLabel' => 'My cases',
    'enableChat' => true,
    'chatLabel' => $chatPartner ? 'Chat with '.$chatPartner->name : 'Chat with client',
])

<div class="case-actions-grid">
    <section class="case-action-card card">
        <div class="case-panel-head">
            <h3>Update stage</h3>
        </div>
        <form method="POST" action="{{ \App\Support\PortalRoute::route('cases.stage', $case) }}" class="case-action-form">
            @csrf
            <div class="form-field">
                <label for="workflow_stage_id">Current stage</label>
                <select name="workflow_stage_id" id="workflow_stage_id">
                    @foreach($stages as $stage)
                        @continue(isset($visibleStageIds) && !in_array($stage->id, $visibleStageIds, true))
                        <option
                            value="{{ $stage->id }}"
                            @selected($case->workflow_stage_id == $stage->id)
                            @disabled(isset($selectableStageIds) && !in_array($stage->id, $selectableStageIds, true) && $case->workflow_stage_id != $stage->id)
                        >
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
                @if(isset($selectableStageIds) && count($selectableStageIds) <= 1)
                    <p class="form-field-hint">Waiting for the previous team step to finish.</p>
                @endif
            </div>
            <div class="form-field">
                <label for="stage_notes">Notes (optional)</label>
                <input type="text" name="notes" id="stage_notes" placeholder="Add a note for this stage change">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Update stage</button>
        </form>
    </section>

    @include('partials.case-report-upload', [
        'case' => $case,
        'storeRoute' => \App\Support\PortalRoute::route('reports.store', $case),
    ])
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

