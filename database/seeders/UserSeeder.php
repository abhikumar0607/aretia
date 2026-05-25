<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $demoCompany = Company::updateOrCreate(
            ['email' => 'demo@acme.test'],
            [
                'name' => 'Acme Corp',
                'phone' => '+1 555 0100',
                'status' => CompanyStatus::Active,
                'approved_at' => now(),
            ]
        );

        $pendingCompany = Company::updateOrCreate(
            ['email' => 'pending@startup.test'],
            [
                'name' => 'Startup Ltd',
                'phone' => '+1 555 0200',
                'status' => CompanyStatus::Pending,
            ]
        );

        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@aretia.test',
                'role' => UserRole::SuperAdmin,
                'phone' => null,
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@aretia.test',
                'role' => UserRole::Admin,
                'phone' => null,
            ],
            [
                'name' => 'Client User',
                'email' => 'client@aretia.test',
                'role' => UserRole::Client,
                'company_id' => $demoCompany->id,
                'phone' => '+1 555 0101',
                'is_primary' => true,
                'onboarding_status' => OnboardingStatus::Active,
            ],
            [
                'name' => 'Analyst User',
                'email' => 'analyst@aretia.test',
                'role' => UserRole::Analyst,
                'phone' => null,
            ],
            [
                'name' => 'Test Superadmin',
                'email' => 'test.superadmin@aretia.com',
                'role' => UserRole::SuperAdmin,
                'phone' => '+92 300 0000001',
            ],
            [
                'name' => 'Test Admin',
                'email' => 'test.admin@aretia.com',
                'role' => UserRole::Admin,
                'phone' => '+92 300 0000002',
            ],
            [
                'name' => 'Test Client',
                'email' => 'test.client@aretia.com',
                'role' => UserRole::Client,
                'company_id' => $demoCompany->id,
                'phone' => '+92 300 0000003',
                'is_primary' => false,
                'onboarding_status' => OnboardingStatus::Active,
            ],
            [
                'name' => 'Test Analyst',
                'email' => 'test.analyst@aretia.com',
                'role' => UserRole::Analyst,
                'phone' => '+92 300 0000004',
            ],
            [
                'name' => 'Pending Client',
                'email' => 'test.pending@aretia.com',
                'role' => UserRole::Client,
                'company_id' => $pendingCompany->id,
                'phone' => '+92 300 0000005',
                'is_primary' => true,
                'onboarding_status' => OnboardingStatus::Registered,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                array_merge([
                    'password' => 'password',
                    'email_verified_at' => now(),
                ], $user)
            );
        }

        $admin = User::where('email', 'admin@aretia.test')->first();
        if ($admin && ! $demoCompany->approved_by) {
            $demoCompany->update(['approved_by' => $admin->id]);
        }
    }
}
