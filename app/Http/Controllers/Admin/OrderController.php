<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::with(['company', 'package', 'user', 'caseFile'])
            ->latest()
            ->paginate(config('portal.per_page'));

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['company', 'package', 'user', 'documents', 'caseFile.stage', 'caseFile.assignee']);

        return view('admin.orders.show', compact('order'));
    }
}
