<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\CaseChatService;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionService $permissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions = app(PermissionService::class);
    }

    public function test_default_case_chat_for_client_and_admin_only(): void
    {
        $this->permissions->seedDefaults();

        $client = User::factory()->create(['role' => UserRole::Client]);
        $analyst = User::factory()->create(['role' => UserRole::Analyst]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($this->permissions->allows($client, Permission::ChatClient));
        $this->assertTrue($this->permissions->allows($admin, Permission::ChatClient));
        $this->assertFalse($this->permissions->allows($analyst, Permission::ChatClient));
    }

    public function test_employees_cannot_send_case_chat(): void
    {
        $analyst = User::factory()->create(['role' => UserRole::Analyst]);

        $this->assertFalse(app(CaseChatService::class)->canSendCaseChat($analyst));
    }

    public function test_universal_permission_denied_when_disabled_in_database(): void
    {
        $this->permissions->seedDefaults();

        RolePermission::query()->updateOrCreate(
            ['role' => UserRole::Client->value, 'permission' => Permission::ChatClient->value],
            ['granted' => false]
        );

        $this->permissions->clearCache();

        $client = User::factory()->create(['role' => UserRole::Client]);

        $this->assertFalse($this->permissions->allows($client, Permission::ChatClient));
    }

    public function test_default_report_permissions_follow_matrix_roles(): void
    {
        $this->permissions->seedDefaults();

        $client = User::factory()->create(['role' => UserRole::Client]);
        $analyst = User::factory()->create(['role' => UserRole::Analyst]);
        $fqa = User::factory()->create(['role' => UserRole::Fqa]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($this->permissions->allows($client, Permission::ReportsView));
        $this->assertFalse($this->permissions->allows($analyst, Permission::ReportsView));
        $this->assertTrue($this->permissions->allows($fqa, Permission::ReportsView));
        $this->assertTrue($this->permissions->allows($fqa, Permission::ReportsManage));
        $this->assertTrue($this->permissions->allows($admin, Permission::ReportsManage));
    }

    public function test_http_chat_inbox_requires_permission(): void
    {
        $this->permissions->seedDefaults();

        RolePermission::query()->updateOrCreate(
            ['role' => UserRole::Client->value, 'permission' => Permission::ChatClient->value],
            ['granted' => false]
        );
        $this->permissions->clearCache();

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'is_active' => true,
        ]);

        $this->assertFalse($client->hasAnyChatPermission());

        $this->actingAs($client, 'web')
            ->getJson(route('chat.inbox.index'))
            ->assertForbidden();
    }
}
