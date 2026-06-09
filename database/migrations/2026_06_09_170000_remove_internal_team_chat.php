<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('messages')->where('channel', 'internal')->delete();
        DB::table('role_permissions')->where('permission', 'chat.internal')->delete();
    }

    public function down(): void
    {
        // Internal chat removed intentionally.
    }
};
