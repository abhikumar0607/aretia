<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_analyst', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['case_id', 'user_id']);
        });

        if (Schema::hasTable('cases') && Schema::hasColumn('cases', 'assigned_to')) {
            $rows = DB::table('cases')->whereNotNull('assigned_to')->get(['id', 'assigned_to']);
            foreach ($rows as $row) {
                DB::table('case_analyst')->insertOrIgnore([
                    'case_id' => $row->id,
                    'user_id' => $row->assigned_to,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_analyst');
    }
};
