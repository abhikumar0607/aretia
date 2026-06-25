<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Exports\OrdersTemplateExport;
use App\Models\Company;
use App\Models\User;
use App\Support\ExcelDownload;
use Database\Seeders\ServicePackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ClientBulkOrderImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_import_orders_from_template(): void
    {
        $this->seed(ServicePackageSeeder::class);

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

        $xlsx = ExcelDownload::xlsx(new OrdersTemplateExport(forAdmin: false), 'template.xlsx')->getContent();
        $file = UploadedFile::fake()->createWithContent('orders.xlsx', $xlsx);

        $this->actingAs($client, 'web')
            ->postJson(route('client.orders.import.store'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_template_includes_package_and_subject_dropdowns(): void
    {
        $this->seed(ServicePackageSeeder::class);

        $xlsx = ExcelDownload::xlsx(new OrdersTemplateExport(forAdmin: false), 'template.xlsx')->getContent();
        $path = tempnam(sys_get_temp_dir(), 'aretia-template-');
        file_put_contents($path, $xlsx);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $orders = $spreadsheet->getSheetByName('Orders');
        $reference = $spreadsheet->getSheetByName('Reference');

        $this->assertNotNull($orders);
        $this->assertNotNull($reference);
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST,
            $orders->getCell('A2')->getDataValidation()->getType()
        );
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST,
            $orders->getCell('C2')->getDataValidation()->getType()
        );
        $this->assertStringContainsString('standard-risk-spectrum', (string) $reference->getCell('C3')->getValue());

        @unlink($path);
    }

    public function test_import_rejects_entire_file_when_due_date_is_in_the_past(): void
    {
        $this->seed(ServicePackageSeeder::class);

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

        $csv = implode("\n", [
            'package_slug,due_date (YYYY-MM-DD),subject_type,subject_name,subject_details,custom_request',
            'standard-risk-spectrum,2020-01-01,entity,Acme Holdings Ltd,Notes,',
        ]);

        $file = UploadedFile::fake()->createWithContent('orders.csv', $csv);

        $this->actingAs($client, 'web')
            ->postJson(route('client.orders.import.store'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('toast.type', 'error')
            ->assertJsonPath('toast.message', 'Import failed: Import cancelled. Row 2 has a due date in the past. Please fix the old date(s) — use today or a future date (YYYY-MM-DD) — and upload the file again.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_bulk_import_attaches_same_documents_to_every_order(): void
    {
        $this->seed(ServicePackageSeeder::class);

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

        $xlsx = ExcelDownload::xlsx(new OrdersTemplateExport(forAdmin: false), 'template.xlsx')->getContent();
        $file = UploadedFile::fake()->createWithContent('orders.xlsx', $xlsx);

        $this->actingAs($client, 'web')
            ->post(route('client.orders.import.store'), [
                'file' => $file,
                'attachments' => [
                    UploadedFile::fake()->create('supporting-brief.pdf', 100, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_documents', 2);
        $this->assertDatabaseHas('order_documents', ['original_name' => 'supporting-brief.pdf']);
    }

    public function test_bulk_import_attaches_multiple_files_to_every_order(): void
    {
        $this->seed(ServicePackageSeeder::class);

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

        $xlsx = ExcelDownload::xlsx(new OrdersTemplateExport(forAdmin: false), 'template.xlsx')->getContent();
        $file = UploadedFile::fake()->createWithContent('orders.xlsx', $xlsx);

        $this->actingAs($client, 'web')
            ->post(route('client.orders.import.store'), [
                'file' => $file,
                'attachments' => [
                    UploadedFile::fake()->create('brief-a.pdf', 100, 'application/pdf'),
                    UploadedFile::fake()->create('brief-b.pdf', 100, 'application/pdf'),
                ],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_documents', 4);
        $this->assertDatabaseHas('order_documents', ['original_name' => 'brief-a.pdf']);
        $this->assertDatabaseHas('order_documents', ['original_name' => 'brief-b.pdf']);
    }

    public function test_bulk_import_extracts_zip_and_attaches_files_to_every_order(): void
    {
        $this->seed(ServicePackageSeeder::class);

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

        $zipPath = tempnam(sys_get_temp_dir(), 'aretia-bulk-zip-');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('inside-a.pdf', '%PDF-1.4 bulk zip test');
        $zip->addFromString('inside-b.pdf', '%PDF-1.4 bulk zip test two');
        $zip->close();

        $xlsx = ExcelDownload::xlsx(new OrdersTemplateExport(forAdmin: false), 'template.xlsx')->getContent();
        $file = UploadedFile::fake()->createWithContent('orders.xlsx', $xlsx);
        $zipFile = UploadedFile::fake()->createWithContent('supporting-docs.zip', (string) file_get_contents($zipPath));

        $this->actingAs($client, 'web')
            ->post(route('client.orders.import.store'), [
                'file' => $file,
                'attachments' => [$zipFile],
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('toast.type', 'success');

        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_documents', 4);
        $this->assertDatabaseHas('order_documents', ['original_name' => 'inside-a.pdf']);
        $this->assertDatabaseHas('order_documents', ['original_name' => 'inside-b.pdf']);

        @unlink($zipPath);
    }
}
