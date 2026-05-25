<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStageHistory extends Model
{
    protected $fillable = [
        'case_id', 'workflow_stage_id', 'user_id', 'notes',
    ];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(WorkflowStage::class, 'workflow_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
