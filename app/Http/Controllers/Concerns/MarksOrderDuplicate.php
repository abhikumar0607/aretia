<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Support\PortalRoute;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait MarksOrderDuplicate
{
    public function markDuplicates(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $orders = Order::query()->whereIn('id', $data['order_ids'])->get();

        foreach ($orders as $order) {
            if (method_exists($this, 'authorizeOrder')) {
                $this->authorizeOrder($order);
            }
        }

        Order::query()
            ->whereIn('id', $data['order_ids'])
            ->update(['marked_as_duplicate' => true]);

        $count = count($data['order_ids']);
        $message = $count === 1
            ? 'Order marked as duplicate.'
            : "{$count} orders marked as duplicate.";

        return Toast::to(PortalRoute::route('orders.index'), $message);
    }
}
