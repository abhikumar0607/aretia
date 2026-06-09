<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseComment extends Model
{
    protected $fillable = ['case_id', 'user_id', 'body'];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorLabel(): string
    {
        $this->loadMissing('user');

        if (! $this->user) {
            return 'Unknown user';
        }

        return $this->user->role->label().' · '.$this->user->name;
    }
}
