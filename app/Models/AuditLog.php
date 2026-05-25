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
            'case.assigned' => 'Case assigned',
            'case.stage_updated' => 'Case stage updated',
            'document.uploaded' => 'Document uploaded',
            'document.downloaded' => 'Document downloaded',
            'message.sent' => 'Message sent',
            'report.delivered' => 'Report delivered',
            'report.downloaded' => 'Report downloaded',
            'workflow_stage.created' => 'Workflow stage created',
            'workflow_stage.deactivated' => 'Workflow stage deactivated',
            default => ucwords(str_replace(['.', '_'], ' ', $this->action)),
        };
    }

    public function actionTone(): string
    {
        $prefix = explode('.', (string) $this->action, 2)[0] ?? '';

        return match ($prefix) {
            'onboarding', 'kyc', 'client' => 'success',
            'order' => 'primary',
            'case', 'workflow' => 'info',
            'document', 'message' => 'muted',
            'report' => 'success',
            default => 'muted',
        };
    }
}
