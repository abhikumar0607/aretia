<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    protected $fillable = [
        'documentable_type', 'documentable_id', 'uploaded_by',
        'type', 'category', 'original_name', 'path',
        'is_encrypted', 'password_hint',
    ];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
