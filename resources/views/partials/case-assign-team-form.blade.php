@php
    $teamDueDates = $case->teamDueDatesByRole();
@endphp
<section class="case-action-card card case-action-card--team">
    <div class="case-panel-head">
        <h3>Assign team</h3>
    </div>

    <form method="POST" action="{{ $assignRoute }}" class="case-action-form case-assign-team-form" id="case-assign-team-form">
        @csrf

        <div class="case-assign-team-fields">
            @foreach(\App\Enums\EmployeeType::cases() as $employeeType)
                @php
                    $options = $employeesByType->get($employeeType->value, collect());
                    $selectedIds = old(
                        'team.'.$employeeType->value,
                        ($teamByType[$employeeType->value] ?? collect())->pluck('id')->all()
                    );
                    $isAnalyst = $employeeType === \App\Enums\EmployeeType::Analyst;
                    $dueDateValue = \App\Support\DueDateRules::formValue(old(
                        'due_dates.'.$employeeType->value,
                        $teamDueDates[$employeeType->value] ?? $case->order->due_date?->format('Y-m-d')
                    ));
                @endphp

                <div class="case-assign-team-role" data-role="{{ $employeeType->value }}" @unless($isAnalyst) data-requires-analyst @endunless>
                    <div class="case-assign-team-field">
                        @include('partials.form-multi-select', [
                            'name' => 'team['.$employeeType->value.']',
                            'label' => $employeeType->label().($isAnalyst ? '' : ' (optional)'),
                            'placeholder' => 'Select '.$employeeType->label().'…',
                            'options' => $options->map(fn ($e) => ['value' => $e->id, 'label' => $e->displayNameWithRole()])->all(),
                            'selected' => $selectedIds,
                            'min' => $isAnalyst ? 1 : 0,
                            'requiredMessage' => 'Analyst is required. Select an Analyst before assigning QA or FQA.',
                            'hint' => $options->isEmpty()
                                ? 'No active '.$employeeType->label().' employee. Add one from Employees.'
                                : null,
                        ])
                    </div>

                    <div class="case-assign-team-field case-assign-due-date">
                        <div class="form-field">
                            <label for="due_date_{{ $employeeType->value }}">
                                {{ $employeeType->label() }} due date
                                @unless($isAnalyst)
                                    <span class="form-optional">(when assigned)</span>
                                @endunless
                            </label>
                            <input
                                type="date"
                                class="due-date-future-only"
                                name="due_dates[{{ $employeeType->value }}]"
                                id="due_date_{{ $employeeType->value }}"
                                value="{{ $dueDateValue }}"
                                min="{{ \App\Support\DueDateRules::minDate() }}"
                                @if($isAnalyst) required @endif
                                data-role-due-date="{{ $employeeType->value }}"
                            >
                            <p class="form-field-hint">Today or a future date only.</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary btn-sm case-assign-team-submit">Save team assignment</button>
    </form>
</section>
