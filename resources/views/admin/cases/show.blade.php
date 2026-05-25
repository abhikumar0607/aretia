@extends('layouts.portal')
@section('title', $case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => route('admin.cases.index'),
    'backLabel' => 'All cases',
])

<div class="case-actions-grid">
    <section class="case-action-card card">
        <div class="case-panel-head">
            <h3>Assign analyst</h3>
        </div>
        <form method="POST" action="{{ route('admin.cases.assign', $case) }}" class="case-action-form">
            @csrf
            <div class="form-field">
                <label for="assigned_to">Analyst</label>
                <select name="assigned_to" id="assigned_to">
                    @foreach($analysts as $analyst)
                        <option value="{{ $analyst->id }}" @selected($case->assigned_to == $analyst->id)>{{ $analyst->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Assign analyst</button>
        </form>
    </section>

    <section class="case-action-card card">
        <div class="case-panel-head">
            <h3>Update stage</h3>
        </div>
        <form method="POST" action="{{ route('admin.cases.stage', $case) }}" class="case-action-form">
            @csrf
            <div class="form-field">
                <label for="workflow_stage_id">Stage</label>
                <select name="workflow_stage_id" id="workflow_stage_id">
                    @foreach($stages as $stage)
                        <option value="{{ $stage->id }}" @selected($case->workflow_stage_id == $stage->id)>{{ $stage->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field">
                <label for="stage_notes">Notes (optional)</label>
                <input type="text" name="notes" id="stage_notes" placeholder="Notes for this stage change">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Update stage</button>
        </form>
    </section>
</div>

@include('partials.case-panel', ['case' => $case, 'showUpload' => false])
@endsection
