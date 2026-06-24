<?php

namespace Tests\Feature;

use App\Exports\OrdersTemplateExport;
use App\Support\ExcelDownload;
use Database\Seeders\ServicePackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OrdersTemplateImportKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_opens_on_orders_sheet_not_reference_sheet(): void
    {
        $this->seed(ServicePackageSeeder::class);

        $xlsx = ExcelDownload::xlsx(new OrdersTemplateExport(forAdmin: true), 'template.xlsx')->getContent();
        $path = tempnam(sys_get_temp_dir(), 'aretia-admin-template-');
        file_put_contents($path, $xlsx);

        $spreadsheet = IOFactory::load($path);

        $this->assertSame('Orders', $spreadsheet->getSheet(0)->getTitle());
        $this->assertSame('Reference', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame(0, $spreadsheet->getActiveSheetIndex());

        @unlink($path);
    }
}
