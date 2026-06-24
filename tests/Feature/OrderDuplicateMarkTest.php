<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDuplicateMarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_mark_selected_orders_as_duplicate(): void
    {
        [$order, $client] = $this->makeOrderScenario();

        $this->actingAs($client, 'web')
            ->post(route('client.orders.mark-duplicate'), [
                'order_ids' => [$order->id],
            ])
            ->assertRedirect(route('client.orders.index'));

        $this->assertTrue($order->fresh()->marked_as_duplicate);
    }

    public function test_client_can_mark_multiple_orders_as_duplicate(): void
    {
        [$orderA, $client] = $this->makeOrderScenario();
        [$orderB] = $this->makeOrderScenario(forCompany: $orderA->company_id, forUser: $client->id);

        $this->actingAs($client, 'web')
            ->post(route('client.orders.mark-duplicate'), [
                'order_ids' => [$orderA->id, $orderB->id],
            ])
            ->assertRedirect(route('client.orders.index'));

        $this->assertTrue($orderA->fresh()->marked_as_duplicate);
        $this->assertTrue($orderB->fresh()->marked_as_duplicate);
    }

    public function test_admin_can_mark_order_as_duplicate(): void
    {
        [$order] = $this->makeOrderScenario();

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'web')
            ->post(route('admin.orders.mark-duplicate'), [
                'order_ids' => [$order->id],
            ])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertTrue($order->fresh()->marked_as_duplicate);
    }

    public function test_client_cannot_mark_another_companys_order(): void
    {
        [$order] = $this->makeOrderScenario();

        $otherCompany = Company::query()->create([
            'name' => 'Other Co',
            'status' => CompanyStatus::Active,
        ]);

        $otherClient = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $otherCompany->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        $this->actingAs($otherClient, 'web')
            ->post(route('client.orders.mark-duplicate'), [
                'order_ids' => [$order->id],
            ])
            ->assertForbidden();

        $this->assertFalse($order->fresh()->marked_as_duplicate);
    }

    /**
     * @return array{0: Order, 1: User}
     */
    private function makeOrderScenario(
        bool $marked = false,
        ?int $forCompany = null,
        ?int $forUser = null,
    ): array {
        $company = $forCompany
            ? Company::query()->findOrFail($forCompany)
            : Company::query()->create([
                'name' => 'Acme Corp',
                'status' => CompanyStatus::Active,
            ]);

        $client = $forUser
            ? User::query()->findOrFail($forUser)
            : User::factory()->create([
                'role' => UserRole::Client,
                'company_id' => $company->id,
                'onboarding_status' => OnboardingStatus::Active,
                'is_active' => true,
            ]);

        $package = ServicePackage::query()->first() ?? ServicePackage::query()->create([
            'name' => 'Standard',
            'slug' => 'standard',
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'reference' => 'ORD-'.uniqid(),
            'company_id' => $company->id,
            'user_id' => $client->id,
            'service_package_id' => $package->id,
            'subject_name' => 'Test Subject',
            'marked_as_duplicate' => $marked,
        ]);

        return [$order, $client];
    }
}
