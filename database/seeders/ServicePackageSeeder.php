<?php

namespace Database\Seeders;

use App\Models\ServicePackage;
use Illuminate\Database\Seeder;

class ServicePackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Basic Risk Spectrum', 'slug' => 'basic-risk-spectrum', 'due_days' => 5, 'description' => 'Entry-level due diligence screening.'],
            ['name' => 'Standard Risk Spectrum', 'slug' => 'standard-risk-spectrum', 'due_days' => 10, 'description' => 'Standard comprehensive risk assessment.'],
            ['name' => 'Standard Risk Spectrum Plus', 'slug' => 'standard-risk-spectrum-plus', 'due_days' => 14, 'description' => 'Enhanced standard package with deeper checks.'],
            ['name' => 'Total Risk Spectrum', 'slug' => 'total-risk-spectrum', 'due_days' => 21, 'description' => 'Full-spectrum due diligence investigation.'],
            ['name' => 'Custom Order', 'slug' => 'custom', 'due_days' => null, 'is_custom' => true, 'description' => 'Describe your custom requirements.'],
        ];

        foreach ($packages as $i => $pkg) {
            ServicePackage::updateOrCreate(
                ['slug' => $pkg['slug']],
                array_merge($pkg, ['sort_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}
