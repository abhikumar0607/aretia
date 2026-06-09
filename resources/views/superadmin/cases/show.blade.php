@extends('layouts.portal')

@section('title', $case->reference)

@section('container_class', 'page-container-wide')

@section('content')

@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => route('superadmin.cases.index'),
    'backLabel' => 'All cases',
    'enableChat' => auth()->user()->hasPermission('chat.client') && $case->canUseCaseChat(auth()->user()),
    'chatLabel' => 'Case chat',
])

@include('partials.case-related-cases', [
    'relatedCases' => $relatedCases ?? collect(),
])

<div class="case-actions-grid">
    @perm('cases.manage')
    @include('partials.case-assign-team-form', [
        'case' => $case,
        'employeesByType' => $employeesByType,
        'teamByType' => $teamByType,
        'assignRoute' => route('superadmin.cases.assign', $case),
    ])

    <section class="case-action-card card">
        <div class="case-panel-head">
            <h3>Update stage</h3>
        </div>

        <form method="POST" action="{{ route('superadmin.cases.stage', $case) }}" class="case-action-form">
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
    @endperm

    @perm('reports.manage')
    @include('partials.case-report-upload', [
        'case' => $case,
        'storeRoute' => route('superadmin.cases.reports.store', $case),
    ])
    @endperm
</div>

@include('partials.case-delivered-reports', ['case' => $case])

@include('partials.case-internal-comments', ['case' => $case])
@include('partials.case-panel', ['case' => $case, 'showUpload' => true])
@perm('chat.client')
@include('partials.case-chat', ['case' => $case])
@endperm

@push('scripts')
@perm('chat.client')
<script src="{{ asset('js/case-chat.js') }}" defer></script>
@endperm
<script src="{{ asset('js/form-multi-select.js') }}" defer></script>
<script src="{{ asset('js/case-assign-team.js') }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('report_password_toggle');
    const wrap = document.getElementById('report_password_wrap');
    if (!toggle || !wrap) return;
    toggle.addEventListener('change', () => { wrap.hidden = !toggle.checked; });
});
</script>
@endpush
@endsection

