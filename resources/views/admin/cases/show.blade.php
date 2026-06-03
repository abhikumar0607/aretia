@extends('layouts.portal')

@section('title', $case->reference)

@section('container_class', 'page-container-wide')



@section('content')

@include('partials.case-hero', [

    'case' => $case,

    'backRoute' => route('admin.cases.index'),

    'backLabel' => 'All cases',

    'enableChat' => true,

    'chatLabel' => 'Open case chat',

])



@include('partials.case-related-cases', [

    'relatedCases' => $relatedCases ?? collect(),

])



<div class="case-actions-grid">

    <section class="case-action-card card">

        <div class="case-panel-head">

            <h3>Assign team</h3>

            <p class="case-panel-sub">You can assign multiple people per role. At least one <strong>Analyst</strong>, one <strong>QA</strong>, and one <strong>FQA</strong> are required. The lead is the assigned Analyst.</p>

        </div>

        <form method="POST" action="{{ route('admin.cases.assign', $case) }}" class="case-action-form">

            @csrf

            @foreach(\App\Enums\EmployeeType::cases() as $employeeType)
                @php
                    $options = $employeesByType->get($employeeType->value, collect());
                    $selectedIds = old(
                        'team.'.$employeeType->value,
                        ($teamByType[$employeeType->value] ?? collect())->pluck('id')->all()
                    );
                @endphp

                @include('partials.form-multi-select', [
                    'name' => 'team['.$employeeType->value.']',
                    'label' => $employeeType->label(),
                    'placeholder' => 'Select '.$employeeType->label().'…',
                    'options' => $options->map(fn ($e) => ['value' => $e->id, 'label' => $e->displayNameWithRole()])->all(),
                    'selected' => $selectedIds,
                    'min' => 1,
                    'hint' => $options->isEmpty()
                        ? 'No active '.$employeeType->label().' employee. Add one from Employees.'
                        : null,
                ])
            @endforeach

            <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.75rem;">Save team assignment</button>

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

    @include('partials.case-report-upload', [
        'case' => $case,
        'storeRoute' => route('admin.cases.reports.store', $case),
    ])

</div>



@include('partials.case-panel', ['case' => $case, 'showUpload' => true])
@include('partials.case-chat', ['case' => $case])

@push('scripts')
<script src="{{ asset('js/case-chat.js') }}" defer></script>
<script src="{{ asset('js/form-multi-select.js') }}" defer></script>
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

