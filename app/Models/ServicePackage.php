<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePackage extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'due_days',
        'is_custom', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
