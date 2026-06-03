<?php

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\KycDocument;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    public function __construct(private AuditService $audit) {}

    public function canManage(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->hasRole(UserRole::SuperAdmin)) {
            return ! $target->hasRole(UserRole::SuperAdmin);
        }

        if ($actor->hasRole(UserRole::Admin)) {
            return $target->hasRole(UserRole::Client) || $target->isEmployee();
        }

        return false;
    }

    public function canManageCompany(User $actor, Company $company): bool
    {
        return $actor->hasRole(UserRole::SuperAdmin) || $actor->hasRole(UserRole::Admin);
    }

    public function deactivateUser(User $actor, User $target): void
    {
        $this->assertCanManage($actor, $target);

        if (! $target->is_active) {
            throw ValidationException::withMessages([
                'user' => 'This account is already deactivated.',
            ]);
        }

        $target->update(['is_active' => false]);
        $this->revokeSessions($target);

        $this->audit->log('user.deactivated', $target, [
            'user_name' => $target->name,
            'user_email' => $target->email,
            'role' => $target->role->value,
        ]);
    }

    public function activateUser(User $actor, User $target): void
    {
        $this->assertCanManage($actor, $target);

        if ($target->is_active) {
            throw ValidationException::withMessages([
                'user' => 'This account is already active.',
            ]);
        }

        if ($target->hasRole(UserRole::Client) && $target->company?->status === CompanyStatus::Suspended) {
            throw ValidationException::withMessages([
                'user' => 'Restore company access first — the whole company is suspended.',
            ]);
        }

        $target->update(['is_active' => true]);

        $this->audit->log('user.activated', $target, [
            'user_name' => $target->name,
            'user_email' => $target->email,
            'role' => $target->role->value,
        ]);
    }

    public function deleteUser(User $actor, User $target): void
    {
        $this->assertCanManage($actor, $target);

        $snapshot = [
            'user_name' => $target->name,
            'user_email' => $target->email,
            'role' => $target->role->value,
            'company_id' => $target->company_id,
        ];

        DB::transaction(function () use ($target, $snapshot) {
            $this->prepareUserDeletion($target);
            $this->revokeSessions($target);
            $this->audit->log('user.deleted', $target, $snapshot);
            $target->delete();
        });
    }

    public function deactivateCompany(User $actor, Company $company): void
    {
        $this->assertCanManageCompany($actor, $company);

        if ($company->status === CompanyStatus::Suspended) {
            throw ValidationException::withMessages([
                'company' => 'This company is already suspended.',
            ]);
        }

        if ($company->status !== CompanyStatus::Active) {
            throw ValidationException::withMessages([
                'company' => 'Only active companies can be suspended.',
            ]);
        }

        $company->update(['status' => CompanyStatus::Suspended]);

        $userIds = $company->users()->pluck('id');
        $company->users()->update(['is_active' => false]);
        $this->revokeSessionsForUsers($userIds);

        $this->audit->log('company.suspended', $company, [
            'company_name' => $company->name,
            'users_affected' => $userIds->count(),
        ]);
    }

    public function activateCompany(User $actor, Company $company): void
    {
        $this->assertCanManageCompany($actor, $company);

        if ($company->status !== CompanyStatus::Suspended) {
            throw ValidationException::withMessages([
                'company' => 'Only suspended companies can be restored.',
            ]);
        }

        $company->update(['status' => CompanyStatus::Active]);
        $company->users()->update(['is_active' => true]);

        $this->audit->log('company.restored', $company, [
            'company_name' => $company->name,
        ]);
    }

    private function prepareUserDeletion(User $target): void
    {
        $target->notifications()->delete();

        if ($target->isEmployee()) {
            DB::table('case_analyst')->where('user_id', $target->id)->delete();
            DB::table('cases')->where('assigned_to', $target->id)->update(['assigned_to' => null]);
            DB::table('cases')->where('assigned_by', $target->id)->update(['assigned_by' => null]);

            return;
        }

        if (! $target->hasRole(UserRole::Client) || ! $target->company_id) {
            return;
        }

        $replacement = User::query()
            ->where('company_id', $target->company_id)
            ->where('role', UserRole::Client)
            ->where('id', '!=', $target->id)
            ->orderByDesc('is_primary')
            ->first();

        if (! $replacement) {
            return;
        }

        Order::where('user_id', $target->id)->update(['user_id' => $replacement->id]);
        KycDocument::where('uploaded_by', $target->id)->update(['uploaded_by' => $replacement->id]);
        DB::table('order_documents')->where('uploaded_by', $target->id)->update(['uploaded_by' => $replacement->id]);
    }

    private function assertCanManage(User $actor, User $target): void
    {
        if (! $this->canManage($actor, $target)) {
            throw ValidationException::withMessages([
                'user' => 'You do not have permission to manage this account.',
            ]);
        }
    }

    private function assertCanManageCompany(User $actor, Company $company): void
    {
        if (! $this->canManageCompany($actor, $company)) {
            abort(403, 'You do not have permission to manage this company.');
        }
    }

    private function revokeSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>|array<int, int>  $userIds
     */
    private function revokeSessionsForUsers($userIds): void
    {
        if ($userIds instanceof \Illuminate\Support\Collection) {
            $userIds = $userIds->all();
        }

        if ($userIds === []) {
            return;
        }

        DB::table('sessions')->whereIn('user_id', $userIds)->delete();
    }
}
