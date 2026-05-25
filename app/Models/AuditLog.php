<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'auditable_type', 'auditable_id',
        'properties', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'client.registered' => 'Client registered',
            'kyc.submitted' => 'KYC submitted',
            'onboarding.approved' => 'Onboarding approved',
            'onboarding.rejected' => 'Onboarding rejected',
            'order.created' => 'Order created',
            'order.due_date_updated' => 'Order due date updated',
            'case.assigned' => 'Case assigned',
            'case.stage_updated' => 'Case stage updated',
            'document.uploaded' => 'Document uploaded',
            'document.downloaded' => 'Document downloaded',
            'message.sent' => 'Message sent',
            'report.delivered' => 'Report delivered',
            'report.downloaded' => 'Report downloaded',
            'workflow_stage.created' => 'Workflow stage created',
            'workflow_stage.deactivated' => 'Workflow stage deactivated',
            'analyst.created' => 'Analyst created',
            default => ucwords(str_replace(['.', '_'], ' ', $this->action)),
        };
    }

    public function actionTone(): string
    {
        $prefix = explode('.', (string) $this->action, 2)[0] ?? '';

        return match ($prefix) {
            'onboarding', 'kyc', 'client', 'analyst' => 'success',
            'order' => 'primary',
            'case', 'workflow' => 'info',
            'document', 'message' => 'muted',
            'report' => 'success',
            default => 'muted',
        };
    }

    public function targetKind(): ?string
    {
        return match ($this->auditable_type) {
            CaseFile::class => 'Case',
            Order::class => 'Order',
            Company::class => 'Company',
            User::class => 'User',
            WorkflowStage::class => 'Workflow',
            Report::class => 'Report',
            Document::class => 'Document',
            KycDocument::class => 'KYC',
            Message::class => 'Message',
            default => null,
        };
    }

    /** Human label for the affected entity (auditable). Falls back to live model when properties are missing. */
    public function targetLabel(): ?string
    {
        $props = $this->properties ?? [];
        $entity = $this->auditable;

        return match ($this->auditable_type) {
            CaseFile::class => $this->joinDot(
                $props['case_reference'] ?? $entity?->reference ?? ('#'.$this->auditable_id),
                $props['company_name'] ?? $entity?->company?->name,
            ),
            Order::class => $this->joinDot(
                $props['order_reference'] ?? $entity?->reference ?? ('#'.$this->auditable_id),
                $props['company_name'] ?? $entity?->company?->name,
            ),
            Company::class => $props['company_name'] ?? $entity?->name ?? ('Company #'.$this->auditable_id),
            User::class => $this->joinDot(
                $props['user_name'] ?? $entity?->name ?? ('User #'.$this->auditable_id),
                $props['user_email'] ?? $entity?->email,
            ),
            WorkflowStage::class => 'Stage · '.($props['stage_name'] ?? $entity?->name ?? ('#'.$this->auditable_id)),
            Report::class => 'Report · case '.($props['case_reference'] ?? $entity?->caseFile?->reference ?? ('#'.$this->auditable_id)),
            Document::class => $this->joinDot(
                'Document · '.($props['document_name'] ?? $entity?->original_name ?? ('#'.$this->auditable_id)),
                ($props['case_reference'] ?? null) ? 'Case '.$props['case_reference'] : null,
            ),
            KycDocument::class => 'KYC · '.($props['document_type'] ?? $entity?->type ?? ('#'.$this->auditable_id)),
            Message::class => 'Case '.($props['case_reference'] ?? $entity?->caseFile?->reference ?? ('#'.$entity?->case_id)),
            default => null,
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function detailChips(): array
    {
        $props = $this->properties ?? [];
        $entity = $this->auditable;
        $chips = [];

        switch ($this->action) {
            case 'case.assigned':
                $leadName = $props['lead_name']
                    ?? ($props['assigned_to'] ?? null ? User::find($props['assigned_to'])?->name : null)
                    ?? $entity?->assignee?->name;

                if ($leadName) {
                    $chips[] = ['label' => 'Lead analyst', 'value' => $leadName];
                }

                $teamNames = $props['analyst_names'] ?? null;
                if (! $teamNames && ! empty($props['analyst_ids']) && is_array($props['analyst_ids'])) {
                    $teamNames = User::whereIn('id', $props['analyst_ids'])->orderBy('name')->pluck('name')->all();
                }
                if (! $teamNames && $entity instanceof CaseFile) {
                    $teamNames = $entity->analysts()->orderBy('name')->pluck('name')->all();
                }

                if (is_array($teamNames) && count($teamNames)) {
                    $others = collect($teamNames)->reject(fn ($n) => $n === $leadName)->values();
                    if ($others->isNotEmpty()) {
                        $chips[] = ['label' => 'Team', 'value' => $others->join(', ')];
                    }
                }
                break;

            case 'case.stage_updated':
                $stageName = $props['stage_name']
                    ?? ($props['workflow_stage_id'] ?? null ? WorkflowStage::find($props['workflow_stage_id'])?->name : null)
                    ?? $entity?->stage?->name;
                if ($stageName) {
                    $chips[] = ['label' => 'New stage', 'value' => $stageName];
                }
                if (! empty($props['notes'])) {
                    $chips[] = ['label' => 'Notes', 'value' => $props['notes']];
                }
                break;

            case 'onboarding.rejected':
                if (! empty($props['reason'])) {
                    $chips[] = ['label' => 'Reason', 'value' => $props['reason']];
                }
                break;

            case 'message.sent':
                $senderName = $props['sender_name']
                    ?? ($props['sender'] ?? null ? User::find($props['sender'])?->name : null);
                $recipientName = $props['recipient_name']
                    ?? ($props['recipient'] ?? null ? User::find($props['recipient'])?->name : null);

                if ($senderName) {
                    $chips[] = ['label' => 'From', 'value' => $senderName];
                }
                if ($recipientName) {
                    $chips[] = ['label' => 'To', 'value' => $recipientName];
                }
                break;

            case 'order.created':
                if (! empty($props['source'])) {
                    $chips[] = ['label' => 'Source', 'value' => str_replace('_', ' ', $props['source'])];
                }
                if ($entity instanceof Order && $entity->package?->name) {
                    $chips[] = ['label' => 'Package', 'value' => $entity->package->name];
                }
                break;

            case 'order.due_date_updated':
                if (! empty($props['previous'])) {
                    $chips[] = ['label' => 'From', 'value' => $props['previous']];
                }
                $chips[] = ['label' => 'To', 'value' => $props['due_date'] ?? 'Cleared'];
                $updater = $props['updated_by_name']
                    ?? ($props['updated_by'] ?? null ? User::find($props['updated_by'])?->name : null);
                if ($updater) {
                    $chips[] = ['label' => 'By', 'value' => $updater];
                }
                break;

            case 'analyst.created':
                if (! empty($props['email'])) {
                    $chips[] = ['label' => 'Email', 'value' => $props['email']];
                }
                if ($entity instanceof User && $entity->name) {
                    $chips[] = ['label' => 'Analyst', 'value' => $entity->name];
                }
                break;

            case 'client.registered':
                $userInfo = null;
                if (! empty($props['user_id'])) {
                    $u = User::find($props['user_id']);
                    if ($u) {
                        $userInfo = $u->name.' · '.$u->email;
                    }
                }
                if ($userInfo) {
                    $chips[] = ['label' => 'User', 'value' => $userInfo];
                }
                break;

            case 'document.uploaded':
            case 'document.downloaded':
                if (! empty($props['document_name'])) {
                    $chips[] = ['label' => 'File', 'value' => $props['document_name']];
                } elseif ($entity instanceof Document && $entity->original_name) {
                    $chips[] = ['label' => 'File', 'value' => $entity->original_name];
                }
                break;
        }

        return $chips;
    }

    private function joinDot(?string $a, ?string $b, string $sep = ' · '): ?string
    {
        $a = trim((string) $a);
        $b = trim((string) $b);

        if ($a === '' && $b === '') {
            return null;
        }

        if ($a === '') {
            return $b;
        }

        if ($b === '') {
            return $a;
        }

        return $a.$sep.$b;
    }
}
