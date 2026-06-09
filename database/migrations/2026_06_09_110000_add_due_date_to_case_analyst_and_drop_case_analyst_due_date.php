<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_analyst', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('user_id');
        });

        if (Schema::hasColumn('cases', 'analyst_due_date')) {
            $rows = DB::table('cases')
                ->whereNotNull('analyst_due_date')
                ->whereNotNull('assigned_to')
                ->get(['id', 'assigned_to', 'analyst_due_date']);

            foreach ($rows as $row) {
                DB::table('case_analyst')
                    ->where('case_id', $row->id)
                    ->where('user_id', $row->assigned_to)
                    ->update(['due_date' => $row->analyst_due_date]);
            }

            Schema::table('cases', function (Blueprint $table) {
                $table->dropColumn('analyst_due_date');
            });
        }
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->date('analyst_due_date')->nullable()->after('assigned_at');
        });

        Schema::table('case_analyst', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
