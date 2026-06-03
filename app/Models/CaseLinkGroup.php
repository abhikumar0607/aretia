<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseLinkGroup extends Model
{
    protected $fillable = ['created_by'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(CaseFile::class, 'case_link_group_id');
    }
}
