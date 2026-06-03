<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_link_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->foreignId('case_link_group_id')
                ->nullable()
                ->after('company_id')
                ->constrained('case_link_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('case_link_group_id');
        });

        Schema::dropIfExists('case_link_groups');
    }
};
