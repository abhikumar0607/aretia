<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'case_id', 'uploaded_by', 'title', 'original_name', 'path',
        'mime_type', 'is_password_protected', 'file_password',
        'delivered_at', 'downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_password_protected' => 'boolean',
            'delivered_at' => 'datetime',
            'downloaded_at' => 'datetime',
        ];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
