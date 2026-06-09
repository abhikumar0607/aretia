@extends('layouts.portal')
@section('title', 'Case '.$case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $canChat = auth()->user()->hasPermission('chat.client') && $case->canUseCaseChat(auth()->user());
@endphp
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => route('client.cases.index'),
    'backLabel' => 'My cases',
    'enableChat' => $canChat,
    'chatLabel' => $canChat ? 'Case chat' : null,
    'heroAction' => $case->order ? '<a href="'.route('client.orders.show', $case->order).'" class="btn btn-secondary btn-sm">View order</a>' : null,
])

@include('partials.case-related-cases', [
    'relatedCases' => $relatedCases ?? collect(),
])

@include('partials.case-delivered-reports', ['case' => $case])

@include('partials.case-panel', ['case' => $case, 'showUpload' => true])
@if($canChat)
    @include('partials.case-chat', ['case' => $case])
@endif
@endsection

@push('scripts')
@if($canChat)
<script src="{{ asset('js/case-chat.js') }}" defer></script>
@endif
@endpush
