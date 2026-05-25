@extends('layouts.portal')
@section('title', 'Report')
@section('content')
<div class="page-header"><h1>{{ $report->title }}</h1><p>Case {{ $report->caseFile->reference }}</p></div>
<div class="card">
    <p><strong>File:</strong> {{ $report->original_name }}</p>
    <p><strong>Delivered:</strong> {{ $report->delivered_at->format('d M Y H:i') }}</p>
    @if($report->is_password_protected)
        <form method="POST" action="{{ route('client.reports.download', $report) }}">
            @csrf
            <label>File password</label>
            <input type="password" name="file_password" required>
            <button type="submit" class="btn btn-primary">Download secure report</button>
        </form>
    @else
        <form method="POST" action="{{ route('client.reports.download', $report) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Download report</button>
        </form>
    @endif
</div>
@endsection
