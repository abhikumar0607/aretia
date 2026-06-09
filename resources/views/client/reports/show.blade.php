@extends('layouts.portal')
@section('title', 'Report')
@section('content')
<div class="page-header"><h1>{{ $report->title }}</h1><p>Case {{ $report->caseFile->reference }}</p></div>
<div class="card">
    <p><strong>File:</strong> {{ $report->original_name }}</p>
    <p><strong>Delivered:</strong> {{ $report->delivered_at->format('d M Y H:i') }}</p>
    @if($report->is_password_protected)
        <p class="form-field-hint" style="margin-bottom:1rem;">Your file password was sent to your email and portal notifications for case <strong>{{ $report->caseFile->reference }}</strong>.</p>
        <form method="POST" action="{{ route('client.reports.download', $report) }}" data-file-download>
            @csrf
            <div class="form-field">
                <label for="file_password">File password</label>
                <input type="password" name="file_password" id="file_password" required>
                @error('file_password')
                    <p class="form-field-error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Download secure report</button>
        </form>
    @else
        <a href="{{ route('client.reports.download', $report) }}" class="btn btn-primary">Download report</a>
    @endif
</div>
@endsection
