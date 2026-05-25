<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStage extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(CaseFile::class, 'workflow_stage_id');
    }
}
