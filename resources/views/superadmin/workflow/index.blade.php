@extends('layouts.portal')
@section('title', 'Workflow Stages')
@section('content')
<div class="page-header"><h1>Workflow stages</h1><p>Add or manage case stages (Pending, In Progress, QA, etc.)</p></div>
<div class="card">
    <form method="POST" action="{{ route('superadmin.workflow.store') }}" class="workflow-stage-form">
        @csrf
        <div class="workflow-stage-form-fields">
            <div class="workflow-stage-form-field workflow-stage-form-field--name">
                <label for="stage_name">Stage name</label>
                <input type="text" id="stage_name" name="name" required placeholder="e.g. In Progress">
            </div>
            <div class="workflow-stage-form-field workflow-stage-form-field--color">
                <label for="stage_color">Color</label>
                <input type="color" id="stage_color" name="color" value="#64748b">
            </div>
            <div class="workflow-stage-form-field workflow-stage-form-field--owner">
                <label for="stage_owner">Owner</label>
                <select id="stage_owner" name="responsible_role">
                    <option value="">Any</option>
                    <option value="analyst">Analyst</option>
                    <option value="qa">QA</option>
                    <option value="fqa">FQA</option>
                </select>
            </div>
            <div class="workflow-stage-form-actions">
                <button type="submit" class="btn btn-primary">Add stage</button>
            </div>
        </div>
    </form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Order</th><th>Name</th><th>Slug</th><th>Owner</th><th>Active</th><th></th></tr></thead>
        <tbody>
        @foreach($stages as $stage)
            <tr>
                <td>{{ $stage->sort_order }}</td>
                <td><span class="badge" style="background:{{ $stage->color }}20;color:{{ $stage->color }}">{{ $stage->name }}</span></td>
                <td>{{ $stage->slug }}</td>
                <td>
                    <form method="POST" action="{{ route('superadmin.workflow.responsible', $stage) }}">
                        @csrf
                        @method('PATCH')
                        <select name="responsible_role" onchange="this.form.submit()">
                            <option value="" @selected(!$stage->responsible_role)>Any</option>
                            <option value="analyst" @selected($stage->responsible_role === 'analyst')>Analyst</option>
                            <option value="qa" @selected($stage->responsible_role === 'qa')>QA</option>
                            <option value="fqa" @selected($stage->responsible_role === 'fqa')>FQA</option>
                        </select>
                    </form>
                </td>
                <td>{{ $stage->is_active ? 'Yes' : 'No' }}</td>
                <td>
                    <div style="display:flex;gap:0.5rem;justify-content:flex-end;flex-wrap:wrap;">
                        @if($stage->is_active)
                            <form method="POST" action="{{ route('superadmin.workflow.destroy', $stage) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Deactivate</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('superadmin.workflow.delete', $stage) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-secondary btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

