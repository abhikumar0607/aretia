@extends('layouts.portal')
@section('title', 'Workflow Stages')
@section('content')
<div class="page-header"><h1>Workflow stages</h1><p>Add or manage case stages (Pending, In Progress, QA, etc.)</p></div>
<div class="card">
    <form method="POST" action="{{ route('admin.workflow.store') }}" style="display:flex;gap:0.75rem;align-items:flex-end;">
        @csrf
        <div style="flex:1;"><label>Stage name</label><input type="text" name="name" required></div>
        <div><label>Color</label><input type="color" name="color" value="#64748b"></div>
        <button type="submit" class="btn btn-primary">Add stage</button>
    </form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Order</th><th>Name</th><th>Slug</th><th>Active</th><th></th></tr></thead>
        <tbody>
        @foreach($stages as $stage)
            <tr>
                <td>{{ $stage->sort_order }}</td>
                <td><span class="badge" style="background:{{ $stage->color }}20;color:{{ $stage->color }}">{{ $stage->name }}</span></td>
                <td>{{ $stage->slug }}</td>
                <td>{{ $stage->is_active ? 'Yes' : 'No' }}</td>
                <td>
                    @if($stage->is_active)
                    <form method="POST" action="{{ route('admin.workflow.destroy', $stage) }}">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Deactivate</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
