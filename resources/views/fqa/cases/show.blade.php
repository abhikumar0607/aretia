@extends('layouts.portal')
@section('title', $case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $canChat = $case->canUseCaseChat(auth()->user());
@endphp
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => \App\Support\PortalRoute::route('cases.index'),
    'backLabel' => 'My cases',
    'enableChat' => $canChat,
    'chatLabel' => $canChat ? 'Case chat' : null,
])

<div class="case-actions-grid">
    @include('partials.case-stage-update-form')

    @perm('reports.manage')
    @include('partials.case-report-upload', [
        'case' => $case,
        'storeRoute' => \App\Support\PortalRoute::route('reports.store', $case),
    ])
    @endperm
</div>

@include('partials.case-delivered-reports', ['case' => $case])

@include('partials.case-internal-comments', ['case' => $case])
@include('partials.case-panel', ['case' => $case])
@if($canChat)
    @include('partials.case-chat', ['case' => $case])
@endif
@endsection

@push('scripts')
@if($canChat)
<script src="{{ asset('js/case-chat.js') }}" defer></script>
@endif
@endpush


