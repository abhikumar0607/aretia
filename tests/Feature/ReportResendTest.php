<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\Report;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Services\PermissionService;
use App\Support\CaseWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportResendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
        $this->seed(\Database\Seeders\WorkflowStageSeeder::class);
    }

    public function test_fqa_can_resend_report_when_prior_delivery_exists_even_if_stage_moved_back(): void
    {
        [$case, $fqa] = $this->makeCaseAtStage(CaseWorkflow::SLUG_RESEARCH_DONE);

        Report::query()->create([
            'case_id' => $case->id,
            'uploaded_by' => $fqa->id,
            'title' => 'First report',
            'original_name' => 'first.pdf',
            'path' => 'uploads/reports/'.$case->id.'/first.pdf',
            'delivered_at' => now()->subDay(),
        ]);

        $pdf = base64_encode('%PDF-1.4 resend');

        $this->actingAs($fqa, 'web')
            ->postJson(route('fqa.reports.store', $case), [
                'title' => 'Updated report',
                'documents' => [
                    ['name' => 'updated.pdf', 'data' => $pdf],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertDatabaseHas('reports', [
            'case_id' => $case->id,
            'title' => 'Updated report',
            'original_name' => 'updated.pdf',
        ]);
    }

    public function test_fqa_cannot_send_first_report_before_fqa_started(): void
    {
        [$case, $fqa] = $this->makeCaseAtStage(CaseWorkflow::SLUG_RESEARCH_DONE);

        $this->actingAs($fqa, 'web')
            ->postJson(route('fqa.reports.store', $case), [
                'title' => 'First report',
                'documents' => [
                    ['name' => 'first.pdf', 'data' => base64_encode('%PDF-1.4')],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'error');
    }

    public function test_fqa_can_resend_report_when_case_is_sent_to_client(): void
    {
        [$case, $fqa] = $this->makeCaseAtStage(CaseWorkflow::SLUG_SENT_TO_CLIENT);

        Report::query()->create([
            'case_id' => $case->id,
            'uploaded_by' => $fqa->id,
            'title' => 'First report',
            'original_name' => 'first.pdf',
            'path' => 'uploads/reports/'.$case->id.'/first.pdf',
            'delivered_at' => now()->subDay(),
        ]);

        $pdf = base64_encode('%PDF-1.4 resend');

        $this->actingAs($fqa, 'web')
            ->postJson(route('fqa.reports.store', $case), [
                'title' => 'Updated report',
                'documents' => [
                    ['name' => 'updated.pdf', 'data' => $pdf],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('reports', [
            'case_id' => $case->id,
            'title' => 'Updated report',
            'original_name' => 'updated.pdf',
        ]);

        $this->assertSame(2, Report::query()->where('case_id', $case->id)->whereNotNull('delivered_at')->count());
    }

    /**
     * @return array{0: CaseFile, 1: User}
     */
    private function makeCaseAtStage(string $stageSlug): array
    {
        $company = Company::query()->create([
            'name' => 'Acme Ltd',
            'status' => CompanyStatus::Active,
        ]);

        $client = User::factory()->create([
            'role' => UserRole::Client,
            'company_id' => $company->id,
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

        $stage = WorkflowStage::query()->where('slug', $stageSlug)->firstOrFail();

        $case = CaseFile::query()->create([
            'reference' => CaseFile::generateReference(),
            'order_id' => $order->id,
            'company_id' => $company->id,
            'workflow_stage_id' => $stage->id,
        ]);

        $fqa = User::factory()->create([
            'role' => UserRole::Fqa,
            'is_active' => true,
        ]);
        $case->analysts()->attach($fqa->id);

        return [$case, $fqa];
    }
}
