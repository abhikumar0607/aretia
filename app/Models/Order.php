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
        'custom_request', 'due_date', 'confirmed_at', 'rejection_reason',
        'marked_as_duplicate',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subject_type' => SubjectType::class,
            'due_date' => 'date',
            'confirmed_at' => 'datetime',
            'marked_as_duplicate' => 'boolean',
        ];
    }

    public function showsDuplicateBadge(): bool
    {
        return (bool) $this->marked_as_duplicate
            || (bool) ($this->getAttribute('has_duplicate_subject') ?? false);
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
        return $this->hasMany(OrderDocument::class)->latest();
    }

    public function documentsForViewer(?User $viewer = null): \Illuminate\Support\Collection
    {
        $viewer ??= auth()->user();

        $documents = $this->relationLoaded('documents')
            ? $this->documents
            : $this->documents()->with('uploader')->get();

        if ($documents->isNotEmpty() && ! $documents->first()->relationLoaded('uploader')) {
            $documents->load('uploader');
        }

        $documents = $documents->sortByDesc('created_at')->values();

        if ($viewer?->hasRole(\App\Enums\UserRole::Client)) {
            return $documents
                ->filter(fn (OrderDocument $doc) => $doc->isVisibleToClient())
                ->values();
        }

        return $documents;
    }

    public function caseFile(): HasOne
    {
        return $this->hasOne(CaseFile::class, 'order_id');
    }

    public static function generateReference(): string
    {
        return 'ORD-'.strtoupper(uniqid());
    }

    public function displayRejectionReason(): ?string
    {
        if ($this->status !== OrderStatus::Rejected) {
            return null;
        }

        $reason = trim((string) ($this->rejection_reason ?? ''));

        return $reason !== '' ? $reason : null;
    }
}
