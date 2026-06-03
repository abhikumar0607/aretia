<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_type', 20)->nullable()->after('role');
        });

        DB::table('users')
            ->where('role', 'analyst')
            ->whereNull('employee_type')
            ->update(['employee_type' => 'analyst']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('employee_type');
        });
    }
};
