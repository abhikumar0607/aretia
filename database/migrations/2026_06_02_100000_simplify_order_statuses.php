<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'rejected']);
        DB::table('orders')->where('status', 'draft')->update(['status' => 'pending']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'rejected')->update(['status' => 'cancelled']);
    }
};
