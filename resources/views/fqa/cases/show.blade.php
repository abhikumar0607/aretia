@extends('layouts.portal')
@section('title', $case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => \App\Support\PortalRoute::route('cases.index'),
    'backLabel' => 'My cases',
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

    @perm('reports.manage')
    @include('partials.case-report-upload', [
        'case' => $case,
        'storeRoute' => \App\Support\PortalRoute::route('reports.store', $case),
    ])
    @endperm
</div>

@include('partials.case-delivered-reports', ['case' => $case])

@include('partials.case-internal-comments', ['case' => $case])
@include('partials.case-panel', ['case' => $case])
@endsection


