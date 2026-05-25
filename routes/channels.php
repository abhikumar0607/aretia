<?php

use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('case.{caseId}', function (User $user, int $caseId): bool {
    $case = CaseFile::find($caseId);
    if (! $case) {
        return false;
    }

    if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
        return true;
    }

    if ($user->hasRole(UserRole::Client) && (int) $case->company_id === (int) $user->company_id) {
        return true;
    }

    if ($user->hasRole(UserRole::Analyst) && (int) $case->assigned_to === (int) $user->id) {
        return true;
    }

    return false;
});
