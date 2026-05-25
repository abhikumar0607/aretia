<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'status',
        'approved_at', 'approved_by', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clientUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Client->value);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(CaseFile::class, 'company_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }
}
