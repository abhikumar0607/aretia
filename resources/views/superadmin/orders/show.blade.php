@extends('layouts.portal')
@section('title', $order->reference)
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.order-review-actions', [
    'order' => $order,
    'approveRoute' => route('superadmin.orders.approve', $order),
    'rejectRoute' => route('superadmin.orders.reject', $order),
])

@include('partials.order-detail', [
    'order' => $order,
    'showCompany' => true,
    'backRoute' => route('superadmin.orders.index'),
    'backLabel' => 'All orders',
    'caseRoute' => $order->caseFile ? route('superadmin.cases.show', $order->caseFile) : null,
    'dueDateAction' => route('superadmin.orders.due-date', $order),
    'documentPreviewRoute' => 'superadmin.orders.documents.preview',
    'documentDownloadRoute' => 'superadmin.orders.documents.download',
    'documentUploadRoute' => route('superadmin.orders.documents.store', $order),
])
@endsection

@push('scripts')
@if(in_array($order->status, [\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Rejected], true) && ! $order->caseFile)
<script src="{{ asset('js/portal-modal.js') }}" defer></script>
@endif
@endpush
