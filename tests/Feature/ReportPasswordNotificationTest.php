<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Models\CaseFile;
use App\Models\Company;
use App\Models\Order;
use App\Models\Report;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\WorkflowStage;
use App\Notifications\ReportReadyNotification;
use App\Services\PermissionService;
use App\Support\CaseWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReportPasswordNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionService::class)->seedDefaults();
        $this->seed(\Database\Seeders\WorkflowStageSeeder::class);
    }

    public function test_client_receives_password_in_email_and_notification_when_report_is_protected(): void
    {
        Notification::fake();

        [$case, $client, $fqa] = $this->makeDeliverableCase();

        $this->actingAs($fqa, 'web')
            ->postJson(route('fqa.reports.store', $case), [
                'title' => 'Protected report',
                'documents' => [
                    ['name' => 'report.pdf', 'data' => base64_encode('%PDF-1.4 protected')],
                ],
                'is_password_protected' => 1,
                'file_password' => 'client-pass-99',
            ])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        Notification::assertSentTo(
            $client,
            ReportReadyNotification::class,
            function (ReportReadyNotification $notification) use ($client): bool {
                $payload = $notification->toArray($client);
                $mail = $notification->toMail($client);
                $viewData = $mail->viewData;

                return str_contains($payload['message'], 'client-pass-99')
                    && ($viewData['highlights']['File password'] ?? null) === 'client-pass-99';
            }
        );
    }

    public function test_admin_and_fqa_can_download_password_protected_report_without_entering_password(): void
    {
        [$case, $client, $fqa] = $this->makeDeliverableCase();

        $report = Report::query()->create([
            'case_id' => $case->id,
            'uploaded_by' => $fqa->id,
            'title' => 'Protected report',
            'original_name' => 'report.pdf',
            'path' => 'uploads/reports/'.$case->id.'/report.pdf',
            'is_password_protected' => true,
            'file_password' => 'client-pass-99',
            'delivered_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'web')
            ->get(route('admin.reports.download', $report))
            ->assertOk()
            ->assertDownload('report.pdf');

        $this->actingAs($fqa, 'web')
            ->get(route('fqa.reports.download', $report))
            ->assertOk()
            ->assertDownload('report.pdf');

        $this->actingAs($client, 'web')
            ->get(route('client.reports.download', $report))
            ->assertRedirect(route('client.reports.show', $report));
    }

    /**
     * @return array{0: CaseFile, 1: User, 2: User}
     */
    private function makeDeliverableCase(): array
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
            'workflow_stage_id' => WorkflowStage::query()->where('slug', CaseWorkflow::SLUG_FQA_STARTED)->value('id'),
        ]);

        $fqa = User::factory()->create([
            'role' => UserRole::Fqa,
            'is_active' => true,
        ]);
        $case->analysts()->attach($fqa->id);

        return [$case, $client, $fqa];
    }
}
