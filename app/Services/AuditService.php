<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Document;
use App\Models\KycDocument;
use App\Models\Message;
use App\Models\Order;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkflowStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(string $action, ?Model $auditable = null, array $properties = []): AuditLog
    {
        $properties = $this->enrich($action, $auditable, $properties);

        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Enrich properties with human-readable names so history remains
     * intact even when target users or stages are later renamed/removed.
     */
    private function enrich(string $action, ?Model $auditable, array $properties): array
    {
        if ($auditable instanceof CaseFile) {
            $properties['case_reference'] = $properties['case_reference'] ?? $auditable->reference;
            $properties['company_name'] = $properties['company_name'] ?? $auditable->company?->name;
        }

        if ($auditable instanceof Order) {
            $properties['order_reference'] = $properties['order_reference'] ?? $auditable->reference;
            $properties['company_name'] = $properties['company_name'] ?? $auditable->company?->name;
        }

        if ($auditable instanceof Company) {
            $properties['company_name'] = $properties['company_name'] ?? $auditable->name;
        }

        if ($auditable instanceof User) {
            $properties['user_name'] = $properties['user_name'] ?? $auditable->name;
            $properties['user_email'] = $properties['user_email'] ?? $auditable->email;
        }

        if ($auditable instanceof WorkflowStage) {
            $properties['stage_name'] = $properties['stage_name'] ?? $auditable->name;
        }

        if ($auditable instanceof Report && $auditable->caseFile) {
            $properties['case_reference'] = $properties['case_reference'] ?? $auditable->caseFile->reference;
        }

        if ($auditable instanceof Document && $auditable->documentable_type === CaseFile::class) {
            $properties['document_name'] = $properties['document_name'] ?? $auditable->original_name;
            $case = CaseFile::find($auditable->documentable_id);
            if ($case) {
                $properties['case_reference'] = $properties['case_reference'] ?? $case->reference;
            }
        }

        if ($auditable instanceof KycDocument) {
            $properties['document_name'] = $properties['document_name'] ?? $auditable->original_name;
            $properties['document_type'] = $properties['document_type'] ?? $auditable->type;
        }

        if ($auditable instanceof Message) {
            $properties['case_reference'] = $properties['case_reference'] ?? $auditable->caseFile?->reference;
        }

        if ($action === 'case.assigned') {
            if (! empty($properties['assigned_to']) && empty($properties['lead_name'])) {
                $lead = User::find($properties['assigned_to']);
                $properties['lead_name'] = $lead?->name;
            }
            if (! empty($properties['analyst_ids']) && empty($properties['analyst_names'])) {
                $properties['analyst_names'] = User::whereIn('id', $properties['analyst_ids'])
                    ->orderBy('name')
                    ->pluck('name')
                    ->all();
            }
        }

        if ($action === 'case.stage_updated' && ! empty($properties['workflow_stage_id']) && empty($properties['stage_name'])) {
            $stage = WorkflowStage::find($properties['workflow_stage_id']);
            $properties['stage_name'] = $stage?->name;
        }

        if ($action === 'message.sent') {
            if (! empty($properties['sender']) && empty($properties['sender_name'])) {
                $properties['sender_name'] = User::find($properties['sender'])?->name;
            }
            if (! empty($properties['recipient']) && empty($properties['recipient_name'])) {
                $properties['recipient_name'] = User::find($properties['recipient'])?->name;
            }
        }

        if ($action === 'order.due_date_updated' && ! empty($properties['updated_by']) && empty($properties['updated_by_name'])) {
            $properties['updated_by_name'] = User::find($properties['updated_by'])?->name;
        }

        return $properties;
    }
}
