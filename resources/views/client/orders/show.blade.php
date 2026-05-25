@extends('layouts.portal')
@section('title', 'Order '.$order->reference)
@section('container_class', 'page-container-wide')

@section('content')
@php
    $caseFile = $order->caseFile;
    $canCaseChat = $caseFile && $caseFile->isChatAvailableFor(auth()->user());
    $orderChatPartner = $canCaseChat ? $caseFile->chatPartnerFor(auth()->user()) : null;
@endphp
@include('partials.order-detail', [
    'order' => $order,
    'showCompany' => false,
    'backRoute' => route('client.orders.index'),
    'backLabel' => 'My orders',
    'caseRoute' => $caseFile ? route('client.cases.show', $caseFile) : null,
    'enableCaseChat' => $canCaseChat,
    'caseChatLabel' => $orderChatPartner ? 'Chat with '.$orderChatPartner->name : null,
    'documentDownloadRoute' => 'client.orders.documents.download',
    'documentUploadRoute' => route('client.orders.documents.store', $order),
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
