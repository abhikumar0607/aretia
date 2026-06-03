<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    protected $fillable = [
        'company_id', 'uploaded_by', 'type', 'subtype',
        'original_name', 'path', 'status',
    ];

    public function subtypeLabel(): ?string
    {
        return \App\Support\KycDocumentLabels::subtypeLabel($this->type, $this->subtype);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
