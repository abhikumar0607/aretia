<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = ['case_id', 'sender_id', 'recipient_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
