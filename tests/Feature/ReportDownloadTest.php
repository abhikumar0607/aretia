<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\Report;
use App\Models\RolePermission;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
    }

    public function test_client_can_download_plain_report_via_get(): void
    {
        [$client, $report] = $this->makeDeliveredReportScenario(passwordProtected: false);

        $this->actingAs($client, 'web')
            ->get(route('client.reports.download', $report))
            ->assertOk()
            ->assertDownload($report->original_name);
    }

    public function test_client_without_permission_cannot_access_reports(): void
    {
        [$client, $report] = $this->makeDeliveredReportScenario();
        $this->denyReports(UserRole::Client);

        $this->actingAs($client, 'web')
            ->get(route('client.reports.index'))
            ->assertForbidden();
    }

    public function test_client_password_report_requires_post_with_correct_password(): void
    {
        [$client, $report] = $this->makeDeliveredReportScenario(passwordProtected: true, password: 'secret-123');

        $this->actingAs($client, 'web')
            ->get(route('client.reports.download', $report))
            ->assertRedirect(route('client.reports.show', $report));

        $this->actingAs($client, 'web')
            ->post(route('client.reports.download', $report), ['file_password' => 'wrong'])
            ->assertSessionHasErrors('file_password');

        $this->actingAs($client, 'web')
            ->post(route('client.reports.download', $report), ['file_password' => 'secret-123'])
            ->assertOk()
            ->assertDownload($report->original_name);
    }

    public function test_superadmin_respects_matrix_when_reports_denied(): void
    {
        $this->denyReports(UserRole::SuperAdmin);

        [$client, $report] = $this->makeDeliveredReportScenario();
        $superadmin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin, 'web')
            ->get(route('superadmin.reports.index'))
            ->assertForbidden();
    }

    public function test_fqa_can_download_report_for_assigned_case_when_granted(): void
    {
        [$client, $report, $case] = $this->makeDeliveredReportScenario(withCase: true);
        $fqa = User::factory()->create([
            'role' => UserRole::Fqa,
            'is_active' => true,
        ]);
        $case->analysts()->attach($fqa->id);

        $this->actingAs($fqa, 'web')
            ->get(route('fqa.reports.download', $report))
            ->assertOk()
            ->assertDownload($report->original_name);
    }

    public function test_analyst_with_permission_sees_only_assigned_case_reports(): void
    {
        [$client, $report, $case] = $this->makeDeliveredReportScenario(withCase: true);
        $otherCase = CaseFile::query()->create([
            'reference' => CaseFile::generateReference(),
            'order_id' => $case->order_id,
            'company_id' => $case->company_id,
        ]);
        Report::query()->create([
            'case_id' => $otherCase->id,
            'uploaded_by' => $client->id,
            'title' => 'Other report',
            'original_name' => 'other.pdf',
            'path' => 'uploads/reports/'.$otherCase->id.'/other.pdf',
            'delivered_at' => now(),
        ]);

        $analyst = User::factory()->create([
            'role' => UserRole::Analyst,
            'is_active' => true,
        ]);
        $case->analysts()->attach($analyst->id);
        $this->grantReportsView(UserRole::Analyst);

        $this->actingAs($analyst, 'web')
            ->get(route('analyst.reports.index'))
            ->assertOk()
            ->assertSee('Final report')
            ->assertDontSee('Other report');
    }

    public function test_analyst_cannot_download_without_permission(): void
    {
        [$client, $report, $case] = $this->makeDeliveredReportScenario(withCase: true);
        $analyst = User::factory()->create([
            'role' => UserRole::Analyst,
            'is_active' => true,
        ]);
        $case->analysts()->attach($analyst->id);

        $this->actingAs($analyst, 'web')
            ->get(route('analyst.reports.download', $report))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Report}|array{0: User, 1: Report, 2: CaseFile}
     */
    private function makeDeliveredReportScenario(
        bool $passwordProtected = false,
        ?string $password = null,
        bool $withCase = false,
    ): array {
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
        ]);

        $dir = public_path('uploads/reports/'.$case->id);
        File::ensureDirectoryExists($dir);
        $filename = 'report.pdf';
        File::put($dir.'/'.$filename, '%PDF-1.4 test');

        $report = Report::query()->create([
            'case_id' => $case->id,
            'uploaded_by' => $client->id,
            'title' => 'Final report',
            'original_name' => $filename,
            'path' => "uploads/reports/{$case->id}/{$filename}",
            'is_password_protected' => $passwordProtected,
            'file_password' => $passwordProtected ? $password : null,
            'delivered_at' => now(),
        ]);

        return $withCase ? [$client, $report, $case] : [$client, $report];
    }

    private function denyReports(UserRole $role): void
    {
        foreach ([Permission::ReportsView, Permission::ReportsManage] as $permission) {
            RolePermission::query()->updateOrCreate(
                ['role' => $role->value, 'permission' => $permission->value],
                ['granted' => false]
            );
        }

        app(PermissionService::class)->clearCache();
    }

    private function grantReportsView(UserRole $role): void
    {
        RolePermission::query()->updateOrCreate(
            ['role' => $role->value, 'permission' => Permission::ReportsView->value],
            ['granted' => true]
        );

        app(PermissionService::class)->clearCache();
    }
}
