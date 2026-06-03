<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employee_type')) {
            return;
        }

        DB::table('users')
            ->where('role', 'analyst')
            ->where('employee_type', 'qa')
            ->update(['role' => 'qa']);

        DB::table('users')
            ->where('role', 'analyst')
            ->where('employee_type', 'fqa')
            ->update(['role' => 'fqa']);

        Schema::table('users', function ($table) {
            $table->dropColumn('employee_type');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'employee_type')) {
            return;
        }

        Schema::table('users', function ($table) {
            $table->string('employee_type', 20)->nullable()->after('role');
        });

        DB::table('users')->where('role', 'analyst')->update(['employee_type' => 'analyst']);
        DB::table('users')->where('role', 'qa')->update(['role' => 'analyst', 'employee_type' => 'qa']);
        DB::table('users')->where('role', 'fqa')->update(['role' => 'analyst', 'employee_type' => 'fqa']);
    }
};
