@extends('layouts.portal')
@section('title', $order->reference)
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.order-review-actions', [
    'order' => $order,
    'approveRoute' => route('admin.orders.approve', $order),
    'rejectRoute' => route('admin.orders.reject', $order),
])

@include('partials.order-detail', [
    'order' => $order,
    'showCompany' => true,
    'backRoute' => route('admin.orders.index'),
    'backLabel' => 'All orders',
    'caseRoute' => $order->caseFile ? route('admin.cases.show', $order->caseFile) : null,
    'dueDateAction' => route('admin.orders.due-date', $order),
    'documentPreviewRoute' => 'admin.orders.documents.preview',
    'documentDownloadRoute' => 'admin.orders.documents.download',
    'documentUploadRoute' => route('admin.orders.documents.store', $order),
])
@endsection

@push('scripts')
@if(in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Rejected], true) && ! $order->caseFile)
<script src="{{ asset('js/portal-modal.js') }}" defer></script>
@endif
@endpush
