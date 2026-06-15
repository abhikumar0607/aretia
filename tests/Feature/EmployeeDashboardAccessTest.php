<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->seed(\Database\Seeders\WorkflowStageSeeder::class);
    }

    /** @return array<string, string> */
    private function roleEmails(): array
    {
        return [
            UserRole::SuperAdmin->value => 'test.superadmin@aretia.com',
            UserRole::Admin->value => 'test.admin@aretia.com',
            UserRole::Analyst->value => 'test.analyst@aretia.com',
            UserRole::Qa->value => 'test.qa@aretia.com',
            UserRole::Fqa->value => 'test.fqa@aretia.com',
            UserRole::Client->value => 'test.client@aretia.com',
        ];
    }

    public function test_all_role_dashboards_load_without_route_errors(): void
    {
        foreach ($this->roleEmails() as $role => $email) {
            $user = User::query()->where('email', $email)->first();
            $this->assertNotNull($user, "Missing seeded user: {$email}");

            $this->actingAs($user, 'web')
                ->get(route("{$role}.dashboard"))
                ->assertOk();
        }
    }
}
