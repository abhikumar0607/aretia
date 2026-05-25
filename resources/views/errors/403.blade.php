@extends('layouts.portal')
@section('title', 'Access Denied')
@section('content')
<div class="card" style="text-align:center;padding:3rem;">
    <h2 style="font-size:1.5rem;margin-bottom:0.5rem;">403 — Access Denied</h2>
    <p style="color:var(--muted);margin-bottom:1.5rem;">You do not have permission to view this page.</p>
    <a href="{{ route(auth()->user()->role->dashboardRoute()) }}" class="btn btn-primary">Go to my dashboard</a>
</div>
@endsection
