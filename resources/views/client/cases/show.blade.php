@extends('layouts.portal')
@section('title', 'Case '.$case->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $canChat = $case->isChatAvailableFor(auth()->user());
    $chatPartner = $canChat ? $case->chatPartnerFor(auth()->user()) : null;
@endphp
@include('partials.case-hero', [
    'case' => $case,
    'backRoute' => route('client.cases.index'),
    'backLabel' => 'My cases',
    'enableChat' => $canChat,
    'chatLabel' => $chatPartner ? 'Chat with '.$chatPartner->name : null,
    'heroAction' => $case->order ? '<a href="'.route('client.orders.show', $case->order).'" class="btn btn-secondary btn-sm">View order</a>' : null,
])

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
