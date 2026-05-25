<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderDueDateService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderDueDateService $dueDates) {}
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

    public function updateDueDate(Request $request, Order $order): JsonResponse|RedirectResponse
    {
        if ($request->boolean('clear_due_date')) {
            $this->dueDates->apply($order, null, $request->user(), true);

            return Toast::back('Due date cleared.');
        }

        $data = $request->validate([
            'due_date' => ['required', 'date'],
        ]);

        $hadDueDate = $order->due_date !== null;

        $this->dueDates->apply(
            $order,
            $this->dueDates->parseOptional($data['due_date']),
            $request->user(),
            $hadDueDate
        );

        return Toast::back('Due date saved. Client and analyst notified.');
    }
}
