<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
    }

    public function test_client_cannot_access_chat_inbox_without_permission(): void
    {
        $this->setUniversalGrant(UserRole::Client, Permission::ChatClient, false);

        $client = $this->makeClient();

        $this->actingAs($client, 'web')
            ->getJson(route('chat.inbox.index'))
            ->assertForbidden();
    }

    public function test_client_can_access_chat_inbox_with_permission(): void
    {
        $this->setUniversalGrant(UserRole::Client, Permission::ChatClient, true);

        $client = $this->makeClient();

        $this->actingAs($client, 'web')
            ->getJson(route('chat.inbox.index'))
            ->assertOk();
    }

    public function test_portal_header_hides_chat_bell_when_permission_denied(): void
    {
        $this->setUniversalGrant(UserRole::Admin, Permission::ChatClient, false);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('id="chat-inbox-bell"', false);
    }

    private function makeClient(): User
    {
        return User::factory()->create([
            'role' => UserRole::Client,
            'is_active' => true,
        ]);
    }

    private function setUniversalGrant(UserRole $role, Permission $permission, bool $granted): void
    {
        RolePermission::query()->updateOrCreate(
            ['role' => $role->value, 'permission' => $permission->value],
            ['granted' => $granted]
        );
        app(PermissionService::class)->clearCache();
    }
}
