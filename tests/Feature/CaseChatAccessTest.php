<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\RolePermission;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\CaseChatService;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseChatAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
    }

    public function test_client_can_use_case_chat_before_case_is_assigned(): void
    {
        [$case, $client] = $this->makeUnassignedCase();

        $this->assertTrue(app(CaseChatService::class)->canUseCaseChat($case, $client));

        $this->actingAs($client, 'web')
            ->getJson(route('cases.messages.index', $case))
            ->assertOk()
            ->assertJsonPath('case.reference', $case->reference);
    }

    public function test_admin_can_use_case_chat_before_case_is_assigned(): void
    {
        [$case] = $this->makeUnassignedCase();
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->assertTrue(app(CaseChatService::class)->canUseCaseChat($case, $admin));

        $this->actingAs($admin, 'web')
            ->getJson(route('cases.messages.index', $case))
            ->assertOk();
    }

    public function test_assigned_analyst_can_use_case_chat_when_permission_granted(): void
    {
        [$case] = $this->makeUnassignedCase();
        RolePermission::query()->updateOrCreate(
            ['role' => UserRole::Analyst->value, 'permission' => Permission::ChatClient->value],
            ['granted' => true],
        );
        app(PermissionService::class)->clearCache();

        $analyst = User::factory()->create([
            'role' => UserRole::Analyst,
            'is_active' => true,
        ]);
        $case->analysts()->attach($analyst->id);

        $this->assertTrue(app(CaseChatService::class)->canUseCaseChat($case, $analyst));

        $this->actingAs($analyst, 'web')
            ->getJson(route('cases.messages.index', $case))
            ->assertOk();
    }

    public function test_assigned_analyst_cannot_use_case_chat_when_permission_denied(): void
    {
        [$case] = $this->makeUnassignedCase();
        $analyst = User::factory()->create([
            'role' => UserRole::Analyst,
            'is_active' => true,
        ]);
        $case->analysts()->attach($analyst->id);

        $this->assertFalse(app(CaseChatService::class)->canUseCaseChat($case, $analyst));
        $this->assertFalse(app(CaseChatService::class)->canSendCaseChat($analyst, $case));

        $this->actingAs($analyst, 'web')
            ->getJson(route('cases.messages.index', $case))
            ->assertForbidden();
    }

    public function test_assigned_fqa_can_use_case_chat_when_permission_granted(): void
    {
        [$case] = $this->makeUnassignedCase();
        RolePermission::query()->updateOrCreate(
            ['role' => UserRole::Fqa->value, 'permission' => Permission::ChatClient->value],
            ['granted' => true],
        );
        app(PermissionService::class)->clearCache();

        $fqa = User::factory()->create([
            'role' => UserRole::Fqa,
            'is_active' => true,
        ]);
        $case->analysts()->attach($fqa->id);

        $this->assertTrue(app(CaseChatService::class)->canUseCaseChat($case, $fqa));

        $this->actingAs($fqa, 'web')
            ->getJson(route('cases.messages.index', $case))
            ->assertOk();
    }

    public function test_unassigned_analyst_cannot_use_case_chat(): void
    {
        [$case] = $this->makeUnassignedCase();
        $analyst = User::factory()->create([
            'role' => UserRole::Analyst,
            'is_active' => true,
        ]);

        $this->assertFalse(app(CaseChatService::class)->canUseCaseChat($case, $analyst));

        $this->actingAs($analyst, 'web')
            ->getJson(route('cases.messages.index', $case))
            ->assertForbidden();
    }

    /**
     * @return array{0: CaseFile, 1: User}
     */
    private function makeUnassignedCase(): array
    {
        $company = Company::query()->create([
            'name' => 'Acme Ltd',
            'status' => CompanyStatus::Active,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        $package = ServicePackage::query()->create([
            'name' => 'Standard',
            'slug' => 'standard',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'reference' => 'ORD-'.uniqid(),
            'company_id' => $company->id,
            'user_id' => $client->id,
            'service_package_id' => $package->id,
            'subject_name' => 'Test subject',
        ]);

        $case = CaseFile::query()->create([
            'reference' => CaseFile::generateReference(),
            'order_id' => $order->id,
            'company_id' => $company->id,
            'assigned_to' => null,
        ]);

        return [$case, $client];
    }
}
