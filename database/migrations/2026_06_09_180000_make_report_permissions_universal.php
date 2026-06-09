<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            ['permission' => 'reports.view', 'role' => 'client', 'granted' => true],
            ['permission' => 'reports.view', 'role' => 'fqa', 'granted' => true],
            ['permission' => 'reports.view', 'role' => 'admin', 'granted' => true],
            ['permission' => 'reports.view', 'role' => 'superadmin', 'granted' => true],
            ['permission' => 'reports.manage', 'role' => 'fqa', 'granted' => true],
            ['permission' => 'reports.manage', 'role' => 'admin', 'granted' => true],
            ['permission' => 'reports.manage', 'role' => 'superadmin', 'granted' => true],
        ];

        foreach ($defaults as $row) {
            DB::table('role_permissions')->updateOrInsert(
                ['permission' => $row['permission'], 'role' => $row['role']],
                ['granted' => $row['granted'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Permissions remain in the matrix; no rollback needed.
    }
};
