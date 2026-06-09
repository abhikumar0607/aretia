<?php

namespace App\Models;

use App\Models\Concerns\HasUploaderAttribution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDocument extends Model
{
    use HasUploaderAttribution;
    protected $fillable = [
        'order_id', 'uploaded_by', 'label', 'original_name', 'path',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
