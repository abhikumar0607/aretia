<section class="case-action-card card">
    <div class="case-panel-head">
        <h3>Update stage</h3>
    </div>

    <p class="case-stage-now">
        Case is currently at:
        <strong>{{ $case->stage?->name ?? 'Assigned' }}</strong>
    </p>

    @if($stageFrozen ?? false)
        <p class="form-field-hint" style="margin:0;">Waiting for the previous team step to finish.</p>
    @else
        <form method="POST" action="{{ \App\Support\PortalRoute::route('cases.stage', $case) }}" class="case-action-form">
            @csrf
            <div class="form-field">
                <label for="workflow_stage_id">Your stage</label>
                <select name="workflow_stage_id" id="workflow_stage_id" @disabled(!($canUpdateStage ?? true))>
                    @foreach($stages as $stage)
                        @continue(!in_array($stage->id, $dropdownStageIds ?? [], true))
                        <option
                            value="{{ $stage->id }}"
                            @selected(($defaultStageId ?? $case->workflow_stage_id) == $stage->id)
                            @disabled(!in_array($stage->id, $selectableStageIds ?? [], true))
                        >
                            {{ $stage->name }}
                        </option>
                    @endforeach
                </select>
                @if(!($canUpdateStage ?? true))
                    <p class="form-field-hint">Your step is complete. The next team will continue.</p>
                @endif
            </div>
            <div class="form-field">
                <label for="stage_notes">Notes (optional)</label>
                <input type="text" name="notes" id="stage_notes" placeholder="Add a note for this stage change" @disabled(!($canUpdateStage ?? true))>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" @disabled(!($canUpdateStage ?? true))>Update stage</button>
        </form>
    @endif
</section>
