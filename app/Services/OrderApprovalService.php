<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Order;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\OrderRejectedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OrderApprovalService
{
    public function __construct(
        private AuditService $audit,
        private OrderDueDateService $dueDates,
        private CaseOrderDocumentService $caseDocuments,
    ) {}

    public function approve(Order $order, User $approver): CaseFile
    {
        $previousStatus = $order->status;
        $this->assertApprovable($order);

        if ($order->caseFile) {
            throw ValidationException::withMessages([
                'order' => 'This order already has a linked case.',
            ]);
        }

        $firstStage = WorkflowStage::where('is_active', true)->orderBy('sort_order')->first();

        $case = CaseFile::create([
            'reference' => CaseFile::generateReference(),
            'order_id' => $order->id,
            'company_id' => $order->company_id,
            'workflow_stage_id' => $firstStage?->id,
            'status' => 'open',
        ]);

        $order->update([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'rejection_reason' => null,
        ]);

        $order->load('documents');
        $this->caseDocuments->syncFromOrder($case);

        $this->audit->log('order.approved', $order, [
            'case_id' => $case->id,
            'case_reference' => $case->reference,
            'approved_by' => $approver->id,
            'previous_status' => $previousStatus->value,
        ]);

        $order->load('user');
        Notification::send($order->user, new OrderConfirmedNotification($order));

        if ($order->due_date) {
            $this->dueDates->notifyDueDateSet($order, false);
        }

        return $case;
    }

    public function reject(Order $order, User $approver, string $reason): void
    {
        $this->assertPending($order);

        $order->update([
            'status' => OrderStatus::Rejected,
            'rejection_reason' => trim($reason),
        ]);

        $this->audit->log('order.rejected', $order, [
            'rejected_by' => $approver->id,
            'reason' => $reason,
        ]);

        $order->load('user');
        Notification::send($order->user, new OrderRejectedNotification($order, $reason));
    }

    private function assertPending(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => 'Only pending orders can be rejected.',
            ]);
        }
    }

    private function assertApprovable(Order $order): void
    {
        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'order' => 'Only pending or rejected orders can be approved.',
            ]);
        }
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    public static function reviewers(): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::SuperAdmin, UserRole::Admin])
            ->where('is_active', true)
            ->get();
    }
}
