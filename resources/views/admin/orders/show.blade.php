@extends('layouts.portal')
@section('title', $order->reference)
@section('container_class', 'page-container-wide')

@section('content')
@include('partials.order-detail', [
    'order' => $order,
    'showCompany' => true,
    'backRoute' => route('admin.orders.index'),
    'backLabel' => 'All orders',
    'caseRoute' => $order->caseFile ? route('admin.cases.show', $order->caseFile) : null,
])
@endsection
