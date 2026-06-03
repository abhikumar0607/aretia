<?php

namespace Database\Seeders;

use App\Models\WorkflowStage;
use Illuminate\Database\Seeder;

class WorkflowStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Assigned', 'slug' => 'assigned', 'color' => '#6366f1', 'sort_order' => 1, 'is_active' => true, 'responsible_role' => 'analyst'],
            ['name' => 'Research started', 'slug' => 'research-started', 'color' => '#3b82f6', 'sort_order' => 2, 'is_active' => true, 'responsible_role' => 'analyst'],
            ['name' => 'Research done', 'slug' => 'research-done', 'color' => '#0ea5e9', 'sort_order' => 3, 'is_active' => true, 'responsible_role' => 'analyst'],
            ['name' => 'QA started', 'slug' => 'qa-started', 'color' => '#f59e0b', 'sort_order' => 4, 'is_active' => true, 'responsible_role' => 'qa'],
            ['name' => 'QA done', 'slug' => 'qa-done', 'color' => '#d97706', 'sort_order' => 5, 'is_active' => true, 'responsible_role' => 'qa'],
            ['name' => 'FQA started', 'slug' => 'fqa-started', 'color' => '#a855f7', 'sort_order' => 6, 'is_active' => true, 'responsible_role' => 'fqa'],
            ['name' => 'Sent to client', 'slug' => 'sent-to-client', 'color' => '#059669', 'sort_order' => 7, 'is_active' => true, 'responsible_role' => 'fqa'],
            ['name' => 'Cancelled', 'slug' => 'cancelled', 'color' => '#dc2626', 'sort_order' => 99, 'is_active' => true, 'responsible_role' => null],
        ];

        foreach ($stages as $stage) {
            WorkflowStage::updateOrCreate(['slug' => $stage['slug']], $stage);
        }
    }
}
