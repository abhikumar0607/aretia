<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Report;
use App\Models\User;
use App\Support\CompanyFilter;

class ReportAccessService
{
    public function canViewReport(User $user): bool
    {
        return $user->hasPermission(Permission::ReportsView)
            || $user->hasPermission(Permission::ReportsManage);
    }

    public function canViewDelivered(Report $report, User $user): bool
    {
        if (! $report->delivered_at || ! $this->canViewReport($user)) {
            return false;
        }

        $report->loadMissing('caseFile');

        if ($user->hasRole(UserRole::Client)) {
            return CompanyFilter::userCanAccessCompany($user, $report->caseFile->company_id);
        }

        if ($user->isEmployee()) {
            return $report->caseFile && $report->caseFile->hasAnalyst($user);
        }

        if ($user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::SuperAdmin)) {
            return true;
        }

        return false;
    }

    public function canViewReportsList(User $user): bool
    {
        if (! $this->canViewReport($user)) {
            return false;
        }

        return $user->hasRole(UserRole::Client)
            || $user->hasRole(UserRole::Admin)
            || $user->hasRole(UserRole::SuperAdmin)
            || $user->isEmployee();
    }

    public function canUploadFinalReport(User $user): bool
    {
        return $user->hasPermission(Permission::ReportsManage);
    }

    public function staffDownloadsWithoutPassword(User $user): bool
    {
        if ($user->hasRole(UserRole::Client) || ! $this->canViewReport($user)) {
            return false;
        }

        return $user->hasRole(UserRole::SuperAdmin)
            || $user->hasRole(UserRole::Admin)
            || $user->hasRole(UserRole::Fqa);
    }
}
