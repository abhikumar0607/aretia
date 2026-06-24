<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\SubjectType;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\OrderConfirmedNotification;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderCreationService
{
    public function __construct(
        private AuditService $audit,
        private OrderDueDateService $dueDates,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromRow(array $data, User $actingUser, bool $forAdmin = false): Order
    {
        $packageSlug = $this->resolvePackageSlug((string) ($data['package_slug'] ?? ''));
        if ($packageSlug === '') {
            throw new \InvalidArgumentException('package_slug is required.');
        }

        $package = ServicePackage::where('slug', $packageSlug)->where('is_active', true)->first();
        if (! $package) {
            throw new \InvalidArgumentException("Unknown package slug: {$packageSlug}");
        }

        if ($forAdmin) {
            $companyName = trim((string) ($data['company_name'] ?? ''));
            if ($companyName === '') {
                throw new \InvalidArgumentException('company_name is required for admin import.');
            }
            $company = Company::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($companyName)])
                ->first();
            if (! $company) {
                throw new \InvalidArgumentException("Company not found: {$companyName}");
            }
            $orderUser = $company->users()->where('is_primary', true)->first()
                ?? $company->users()->first();
            if (! $orderUser) {
                throw new \InvalidArgumentException("No user found for company: {$companyName}");
            }
        } else {
            // Client portal: always the acting user's company (never from spreadsheet).
            $company = $actingUser->company;
            $orderUser = $actingUser;
            if (! $company) {
                throw new \InvalidArgumentException('Your account is not linked to a company.');
            }
        }

        if ($package->is_custom) {
            $customRequest = trim((string) ($data['custom_request'] ?? ''));
            if ($customRequest === '') {
                throw new \InvalidArgumentException('custom_request is required for custom orders.');
            }
            $subjectTypeRaw = strtolower(trim((string) ($data['subject_type'] ?? '')));
            if (! in_array($subjectTypeRaw, ['individual', 'entity'], true)) {
                throw new \InvalidArgumentException('subject_type must be individual or entity.');
            }
            $subjectType = SubjectType::from($subjectTypeRaw);
            $subjectName = trim((string) ($data['subject_name'] ?? '')) ?: null;
            $subjectDetails = trim((string) ($data['subject_details'] ?? '')) ?: null;
        } else {
            $subjectTypeRaw = strtolower(trim((string) ($data['subject_type'] ?? '')));
            if (! in_array($subjectTypeRaw, ['individual', 'entity'], true)) {
                throw new \InvalidArgumentException('subject_type must be individual or entity.');
            }
            $subjectName = trim((string) ($data['subject_name'] ?? ''));
            if ($subjectName === '') {
                throw new \InvalidArgumentException('subject_name is required.');
            }
            $subjectType = SubjectType::from($subjectTypeRaw);
            $subjectDetails = trim((string) ($data['subject_details'] ?? '')) ?: null;
            $customRequest = null;
        }

        $dueDate = $this->dueDates->parseOptional($data['due_date'] ?? null);
        $autoConfirm = $forAdmin;

        $order = Order::create([
            'reference' => Order::generateReference(),
            'company_id' => $company->id,
            'user_id' => $orderUser->id,
            'service_package_id' => $package->id,
            'status' => $autoConfirm ? OrderStatus::Confirmed : OrderStatus::Pending,
            'subject_type' => $subjectType,
            'subject_name' => $subjectName,
            'subject_details' => $subjectDetails,
            'custom_request' => $customRequest,
            'due_date' => $dueDate,
            'confirmed_at' => $autoConfirm ? now() : null,
        ]);

        if ($autoConfirm) {
            $case = $this->createCaseForOrder($order);

            $this->audit->log('order.created', $order, [
                'case_id' => $case->id,
                'source' => $forAdmin ? 'admin_import' : 'portal',
                'created_by' => $actingUser->id,
            ]);

            Notification::send($orderUser, new OrderConfirmedNotification($order));

            if ($dueDate) {
                $this->dueDates->notifyDueDateSet($order, false);
            }
        } else {
            $this->audit->log('order.submitted', $order, [
                'submitted_by' => $actingUser->id,
            ]);

            $reviewers = OrderApprovalService::reviewers();
            if ($reviewers->isNotEmpty()) {
                Notification::send($reviewers, new OrderSubmittedNotification($order));
            }
        }

        return $order;
    }

    private function resolvePackageSlug(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (str_contains($raw, ' — ')) {
            return Str::slug(trim(explode(' — ', $raw, 2)[0]));
        }

        return Str::slug($raw);
    }

    private function createCaseForOrder(Order $order): CaseFile
    {
        $firstStage = WorkflowStage::where('is_active', true)->orderBy('sort_order')->first();

        return CaseFile::create([
            'reference' => CaseFile::generateReference(),
            'order_id' => $order->id,
            'company_id' => $order->company_id,
            'workflow_stage_id' => $firstStage?->id,
            'status' => 'open',
        ]);
    }
}
