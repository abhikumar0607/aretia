<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogCaseSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
    }

    public function test_audit_trail_can_be_filtered_by_case_reference(): void
    {
        [$caseA, $caseB, $admin] = $this->makeAuditScenario();

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'case.stage_updated',
            'auditable_type' => CaseFile::class,
            'auditable_id' => $caseA->id,
            'properties' => ['case_reference' => $caseA->reference],
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'case.stage_updated',
            'auditable_type' => CaseFile::class,
            'auditable_id' => $caseB->id,
            'properties' => ['case_reference' => $caseB->reference],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.audit.index', ['q' => $caseA->reference]))
            ->assertOk()
            ->assertSee($caseA->reference)
            ->assertDontSee($caseB->reference);
    }

    public function test_audit_trail_lists_case_subject_for_case_related_entries(): void
    {
        [$caseA, , $admin] = $this->makeAuditScenario();

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'message.sent',
            'auditable_type' => CaseFile::class,
            'auditable_id' => $caseA->id,
            'properties' => [
                'case_reference' => $caseA->reference,
                'company_name' => 'Acme Ltd',
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.audit.index', ['q' => $caseA->reference]))
            ->assertOk()
            ->assertSee('Subject A');
    }

    public function test_audit_trail_can_be_filtered_by_case_subject(): void
    {
        [$caseA, $caseB, $admin] = $this->makeAuditScenario();

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'message.sent',
            'auditable_type' => CaseFile::class,
            'auditable_id' => $caseA->id,
            'properties' => ['case_reference' => $caseA->reference],
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'message.sent',
            'auditable_type' => CaseFile::class,
            'auditable_id' => $caseB->id,
            'properties' => ['case_reference' => $caseB->reference],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.audit.index', ['q' => 'Subject A']))
            ->assertOk()
            ->assertSee($caseA->reference)
            ->assertDontSee($caseB->reference);
    }

    public function test_audit_trail_pagination_preserves_case_search_query(): void
    {
        [$case, , $admin] = $this->makeAuditScenario();

        for ($i = 0; $i < 12; $i++) {
            AuditLog::query()->create([
                'user_id' => $admin->id,
                'action' => 'case.comment_added',
                'auditable_type' => CaseFile::class,
                'auditable_id' => $case->id,
                'properties' => [
                    'case_reference' => $case->reference,
                    'note' => 'Comment '.$i,
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        $response = $this->actingAs($admin, 'web')
            ->get(route('admin.audit.index', ['q' => $case->reference, 'page' => 2]))
            ->assertOk();

        $response->assertSee('q='.$case->reference, false);
        $response->assertSee('aria-current="page">2</span>', false);
        $response->assertSee('<strong>12</strong>', false);
    }

    /**
     * @return array{0: CaseFile, 1: CaseFile, 2: User}
     */
    private function makeAuditScenario(): array
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

        $orderA = Order::query()->create([
            'reference' => 'ORD-A-'.uniqid(),
            'company_id' => $company->id,
            'user_id' => $client->id,
            'service_package_id' => $package->id,
            'subject_name' => 'Subject A',
        ]);

        $orderB = Order::query()->create([
            'reference' => 'ORD-B-'.uniqid(),
            'company_id' => $company->id,
            'user_id' => $client->id,
            'service_package_id' => $package->id,
            'subject_name' => 'Subject B',
        ]);

        $caseA = CaseFile::query()->create([
            'reference' => 'CASE-SEARCH-A',
            'order_id' => $orderA->id,
            'company_id' => $company->id,
        ]);

        $caseB = CaseFile::query()->create([
            'reference' => 'CASE-SEARCH-B',
            'order_id' => $orderB->id,
            'company_id' => $company->id,
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        return [$caseA, $caseB, $admin];
    }
}
