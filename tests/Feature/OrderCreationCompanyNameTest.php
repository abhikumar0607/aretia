<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreationCompanyNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_creation_uses_company_name(): void
    {
        $company = Company::query()->create([
            'name' => 'Acme Corp',
            'email' => 'billing@acme.test',
            'status' => CompanyStatus::Active,
        ]);

        User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'is_primary' => true,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        ServicePackage::query()->create([
            'name' => 'Standard',
            'slug' => 'standard-risk-spectrum',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $order = app(OrderCreationService::class)->createFromRow([
            'company_name' => 'Acme Corp',
            'package_slug' => 'standard-risk-spectrum',
            'subject_type' => 'entity',
            'subject_name' => 'Test Subject',
        ], $admin, true);

        $this->assertSame($company->id, $order->company_id);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
    }

    public function test_client_import_ignores_company_name_in_spreadsheet(): void
    {
        $company = Company::query()->create([
            'name' => 'Client Co',
            'status' => CompanyStatus::Active,
        ]);

        Company::query()->create([
            'name' => 'Other Co',
            'status' => CompanyStatus::Active,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        ServicePackage::query()->create([
            'name' => 'Standard',
            'slug' => 'standard-risk-spectrum',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $order = app(OrderCreationService::class)->createFromRow([
            'company_name' => 'Other Co',
            'package_slug' => 'standard-risk-spectrum',
            'subject_type' => 'entity',
            'subject_name' => 'Test Subject',
        ], $client, false);

        $this->assertSame($company->id, $order->company_id);
    }

    public function test_client_order_creation_does_not_need_company_name(): void
    {
        $company = Company::query()->create([
            'name' => 'Client Co',
            'status' => CompanyStatus::Active,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        ServicePackage::query()->create([
            'name' => 'Standard',
            'slug' => 'standard-risk-spectrum',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $order = app(OrderCreationService::class)->createFromRow([
            'package_slug' => 'standard-risk-spectrum',
            'subject_type' => 'entity',
            'subject_name' => 'Test Subject',
        ], $client, false);

        $this->assertSame($company->id, $order->company_id);
        $this->assertSame(OrderStatus::Pending, $order->status);
    }
}
