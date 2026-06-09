@extends('layouts.portal')
@section('title', 'Order '.$order->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $caseFile = $order->caseFile;
    $canCaseChat = auth()->user()->hasPermission('chat.client') && $caseFile && $caseFile->canUseCaseChat(auth()->user());
@endphp
@include('partials.order-detail', [
    'order' => $order,
    'showCompany' => false,
    'backRoute' => route('client.orders.index'),
    'backLabel' => 'My orders',
    'caseRoute' => $caseFile ? route('client.cases.show', $caseFile) : null,
    'enableCaseChat' => $canCaseChat,
    'caseChatLabel' => $canCaseChat ? 'Case chat' : null,
    'documentPreviewRoute' => 'client.orders.documents.preview',
    'documentDownloadRoute' => 'client.orders.documents.download',
    'documentUploadRoute' => route('client.orders.documents.store', $order),
    'dueDateAction' => route('client.orders.due-date', $order),
])

@if($canCaseChat)
    @include('partials.case-chat', ['case' => $caseFile])
@endif
@endsection

@push('scripts')
@if($canCaseChat)
<script src="{{ asset('js/case-chat.js') }}" defer></script>
@endif
@endpush
