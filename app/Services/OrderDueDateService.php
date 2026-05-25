<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderDueDateSetNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class OrderDueDateService
{
    public function __construct(private AuditService $audit) {}

    public function parseOptional(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    public function apply(Order $order, ?Carbon $newDueDate, User $actor, bool $isUpdate): bool
    {
        $previous = $order->due_date?->format('Y-m-d');
        $next = $newDueDate?->format('Y-m-d');

        if ($previous === $next) {
            return false;
        }

        $order->update(['due_date' => $newDueDate]);

        $this->audit->log('order.due_date_updated', $order, [
            'due_date' => $next,
            'previous' => $previous,
            'updated_by' => $actor->id,
        ]);

        if ($newDueDate) {
            $this->notifyDueDateSet($order, $isUpdate || $previous !== null);
        }

        return true;
    }

    public function notifyDueDateSet(Order $order, bool $isUpdate = false): void
    {
        $order->loadMissing(['company', 'package', 'caseFile.assignee']);

        $recipients = $this->dueDateRecipients($order);
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new OrderDueDateSetNotification($order, $isUpdate));
    }

    /** @return Collection<int, User> */
    private function dueDateRecipients(Order $order): Collection
    {
        $recipients = User::query()
            ->where('company_id', $order->company_id)
            ->where('role', UserRole::Client)
            ->get();

        $analyst = $order->caseFile?->assignee;
        if ($analyst) {
            $recipients = $recipients->push($analyst)->unique('id')->values();
        }

        return $recipients;
    }
}
