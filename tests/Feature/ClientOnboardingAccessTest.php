<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientOnboardingAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_client_is_redirected_from_onboarding_to_dashboard(): void
    {
        $company = Company::query()->create([
            'name' => 'Active Co',
            'status' => CompanyStatus::Active,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        $this->actingAs($client, 'web')
            ->get(route('client.onboarding'))
            ->assertRedirect(route('client.dashboard'));
    }

    public function test_pending_client_can_access_onboarding(): void
    {
        $company = Company::query()->create([
            'name' => 'Pending Co',
            'status' => CompanyStatus::Pending,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'onboarding_status' => OnboardingStatus::Registered,
            'is_active' => true,
        ]);

        $this->actingAs($client, 'web')
            ->get(route('client.onboarding'))
            ->assertOk();
    }
}
