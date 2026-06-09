<?php

namespace App\Models\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasUploaderAttribution
{
    abstract public function uploader(): BelongsTo;

    public function isUploadedByClient(): bool
    {
        $this->loadMissing('uploader');

        return $this->uploader?->hasRole(UserRole::Client) ?? false;
    }

    public function isVisibleToClient(): bool
    {
        return $this->isUploadedByClient();
    }

    public function uploadAttributionLabel(): string
    {
        $this->loadMissing('uploader');

        if (! $this->uploader) {
            return 'Uploaded by unknown user';
        }

        return 'Uploaded by '.$this->uploader->role->label().' · '.$this->uploader->name;
    }
}
