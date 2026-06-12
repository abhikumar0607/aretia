<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeleteRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
    }

    public function test_super_admin_delete_from_clients_index_stays_on_index(): void
    {
        [$companyA, $companyB, $superAdmin, $target] = $this->seedCompaniesWithUsers();

        $indexUrl = route('superadmin.clients.index');

        $this->actingAs($superAdmin, 'web')
            ->from($indexUrl)
            ->delete(route('superadmin.users.destroy', $target))
            ->assertRedirect($indexUrl)
            ->assertSessionHas('toast.message', "{$target->name}'s account was deleted permanently.");

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $companyB->users()->first()->id]);
        $this->assertDatabaseHas('companies', ['id' => $companyA->id]);
    }

    public function test_super_admin_delete_from_company_show_stays_on_same_company(): void
    {
        [$companyA, , $superAdmin, $target] = $this->seedCompaniesWithUsers();

        $showUrl = route('superadmin.clients.show', $companyA);

        $this->actingAs($superAdmin, 'web')
            ->from($showUrl)
            ->delete(route('superadmin.users.destroy', $target))
            ->assertRedirect($showUrl)
            ->assertSessionHas('toast.message', "{$target->name}'s account was deleted permanently.");
    }

    /**
     * @return array{0: Company, 1: Company, 2: User, 3: User}
     */
    private function seedCompaniesWithUsers(): array
    {
        $companyA = Company::query()->create([
            'name' => 'Company A',
            'status' => CompanyStatus::Active,
        ]);

        $companyB = Company::query()->create([
            'name' => 'Company B',
            'status' => CompanyStatus::Active,
        ]);

        $target = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $companyA->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
            'name' => 'Delete Me',
        ]);

        User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $companyB->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
            'name' => 'Other Client',
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        return [$companyA, $companyB, $superAdmin, $target];
    }
}
