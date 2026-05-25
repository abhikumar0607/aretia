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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class OrderCreationService
{
    public function __construct(private AuditService $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromRow(array $data, User $actingUser, bool $forAdmin = false): Order
    {
        $packageSlug = Str::slug(trim((string) ($data['package_slug'] ?? '')));
        if ($packageSlug === '') {
            throw new \InvalidArgumentException('package_slug is required.');
        }

        $package = ServicePackage::where('slug', $packageSlug)->where('is_active', true)->first();
        if (! $package) {
            throw new \InvalidArgumentException("Unknown package slug: {$packageSlug}");
        }

        if ($forAdmin) {
            $companyEmail = trim((string) ($data['company_email'] ?? ''));
            if ($companyEmail === '') {
                throw new \InvalidArgumentException('company_email is required for admin import.');
            }
            $company = Company::where('email', $companyEmail)->first();
            if (! $company) {
                throw new \InvalidArgumentException("Company not found: {$companyEmail}");
            }
            $orderUser = $company->users()->where('is_primary', true)->first()
                ?? $company->users()->first();
            if (! $orderUser) {
                throw new \InvalidArgumentException("No user found for company: {$companyEmail}");
            }
        } else {
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
            $subjectType = null;
            $subjectName = null;
            $subjectDetails = null;
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

        $order = Order::create([
            'reference' => Order::generateReference(),
            'company_id' => $company->id,
            'user_id' => $orderUser->id,
            'service_package_id' => $package->id,
            'status' => OrderStatus::Confirmed,
            'subject_type' => $subjectType,
            'subject_name' => $subjectName,
            'subject_details' => $subjectDetails,
            'custom_request' => $customRequest,
            'due_date' => $package->due_days ? now()->addDays($package->due_days) : null,
            'confirmed_at' => now(),
        ]);

        $firstStage = WorkflowStage::where('is_active', true)->orderBy('sort_order')->first();

        $case = CaseFile::create([
            'reference' => CaseFile::generateReference(),
            'order_id' => $order->id,
            'company_id' => $company->id,
            'workflow_stage_id' => $firstStage?->id,
            'status' => 'open',
        ]);

        $this->audit->log('order.created', $order, [
            'case_id' => $case->id,
            'source' => 'excel_import',
            'imported_by' => $actingUser->id,
        ]);

        Notification::send($orderUser, new OrderConfirmedNotification($order));

        return $order;
    }
}
