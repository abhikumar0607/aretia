<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\SubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'reference', 'company_id', 'user_id', 'service_package_id',
        'status', 'subject_type', 'subject_name', 'subject_details',
        'custom_request', 'due_date', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subject_type' => SubjectType::class,
            'due_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_package_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrderDocument::class);
    }

    public function caseFile(): HasOne
    {
        return $this->hasOne(CaseFile::class, 'order_id');
    }

    public static function generateReference(): string
    {
        return 'ORD-'.strtoupper(uniqid());
    }
}
