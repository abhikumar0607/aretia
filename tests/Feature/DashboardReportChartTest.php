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
use App\Support\DashboardChartData;
use App\Support\DashboardFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReportChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_report_chart_counts_cases_awaiting_delivery_as_in_progress(): void
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
            'slug' => 'standard-risk-spectrum',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $stage = WorkflowStage::query()->create([
            'name' => 'Order confirmed',
            'slug' => 'order-confirmed',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $awaitingOrder = Order::query()->create([
            'reference' => 'ORD-001',
            'company_id' => $company->id,
            'user_id' => $client->id,
            'service_package_id' => $package->id,
            'status' => 'confirmed',
            'subject_type' => 'entity',
            'subject_name' => 'Pending Co',
        ]);

        $awaitingCase = CaseFile::query()->create([
            'reference' => 'CASE-001',
            'company_id' => $company->id,
            'order_id' => $awaitingOrder->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'open',
        ]);

        $deliveredOrder = Order::query()->create([
            'reference' => 'ORD-002',
            'company_id' => $company->id,
            'user_id' => $client->id,
            'service_package_id' => $package->id,
            'status' => 'confirmed',
            'subject_type' => 'entity',
            'subject_name' => 'Delivered Co',
        ]);

        $deliveredCase = CaseFile::query()->create([
            'reference' => 'CASE-002',
            'company_id' => $company->id,
            'order_id' => $deliveredOrder->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'open',
        ]);

        Report::query()->create([
            'case_id' => $deliveredCase->id,
            'uploaded_by' => $client->id,
            'title' => 'Final report',
            'original_name' => 'report.pdf',
            'path' => 'uploads/reports/1/report.pdf',
            'delivered_at' => now(),
        ]);

        $charts = DashboardChartData::forClient($company->id, new DashboardFilters);
        $reportChart = collect($charts)->firstWhere('key', 'reports');

        $this->assertNotNull($reportChart);
        $this->assertSame(['Delivered', 'In progress'], $reportChart['labels']);
        $this->assertSame([1, 1], $reportChart['values']);
    }
}
