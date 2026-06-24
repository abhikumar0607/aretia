<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\CaseTeamAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DueDateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_team_assignment_rejects_past_analyst_due_date(): void
    {
        [$case, $analyst] = $this->makeCaseScenario();

        $service = app(CaseTeamAssignmentService::class);

        $this->expectException(ValidationException::class);

        $memberIds = $service->validateTeamPayload([
            'analyst' => [$analyst->id],
            'qa' => [],
            'fqa' => [],
        ]);

        $service->validateDueDates($memberIds, [
            'analyst' => now()->subDay()->toDateString(),
            'qa' => now()->addWeek()->toDateString(),
            'fqa' => now()->addWeek()->toDateString(),
        ]);
    }

    public function test_form_value_clears_past_dates(): void
    {
        $past = now()->subDay()->toDateString();
        $today = now()->toDateString();
        $future = now()->addWeek()->toDateString();

        $this->assertSame('', \App\Support\DueDateRules::formValue($past));
        $this->assertSame($today, \App\Support\DueDateRules::formValue($today));
        $this->assertSame($future, \App\Support\DueDateRules::formValue($future));
    }

    public function test_admin_cannot_set_past_order_due_date(): void
    {
        [$order] = $this->makeOrderScenario();

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.orders.due-date', $order), [
                'due_date' => now()->subDays(3)->toDateString(),
            ])
            ->assertSessionHasErrors('due_date');

        $this->assertNull($order->fresh()->due_date);
    }

  /**
     * @return array{0: CaseFile, 1: User}
     */
    private function makeCaseScenario(): array
    {
        $company = Company::query()->create([
            'name' => 'Acme Corp',
            'status' => CompanyStatus::Active,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'onboarding_status' => OnboardingStatus::Active,
            'is_active' => true,
        ]);

        $analyst = User::factory()->create([
            'role' => UserRole::Analyst,
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
            'subject_name' => 'Subject',
        ]);

        $case = CaseFile::query()->create([
            'reference' => 'CASE-'.uniqid(),
            'order_id' => $order->id,
            'company_id' => $company->id,
        ]);

        return [$case, $analyst];
    }

    /**
     * @return array{0: Order}
     */
    private function makeOrderScenario(): array
    {
        $company = Company::query()->create([
            'name' => 'Acme Corp',
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
            'subject_name' => 'Subject',
        ]);

        return [$order];
    }
}
