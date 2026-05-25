<?php

namespace Database\Seeders;

use App\Models\WorkflowStage;
use Illuminate\Database\Seeder;

class WorkflowStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Pending', 'slug' => 'pending', 'color' => '#94a3b8', 'sort_order' => 1],
            ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#3b82f6', 'sort_order' => 2],
            ['name' => 'QA', 'slug' => 'qa', 'color' => '#d97706', 'sort_order' => 3],
            ['name' => 'Completed', 'slug' => 'completed', 'color' => '#059669', 'sort_order' => 4],
        ];

        foreach ($stages as $stage) {
            WorkflowStage::updateOrCreate(['slug' => $stage['slug']], $stage);
        }
    }
}
