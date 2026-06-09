<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('channel', 20)->default('client')->after('recipient_id');
            $table->index(['case_id', 'channel']);
        });

        $this->migrateChatPermissions();
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['case_id', 'channel']);
            $table->dropColumn('channel');
        });

        DB::table('role_permissions')->whereIn('permission', ['chat.client', 'chat.internal'])->delete();
    }

    private function migrateChatPermissions(): void
    {
        $defaults = [
            'client' => ['chat.client' => true, 'chat.internal' => false],
            'analyst' => ['chat.client' => false, 'chat.internal' => true],
            'qa' => ['chat.client' => false, 'chat.internal' => true],
            'fqa' => ['chat.client' => false, 'chat.internal' => true],
            'admin' => ['chat.client' => true, 'chat.internal' => false],
            'superadmin' => ['chat.client' => true, 'chat.internal' => false],
        ];

        $legacy = DB::table('role_permissions')
            ->where('permission', 'chat.use')
            ->get(['role', 'granted']);

        foreach ($defaults as $role => $permissions) {
            $legacyGrant = $legacy->firstWhere('role', $role);

            foreach ($permissions as $permission => $defaultGranted) {
                $granted = $legacyGrant !== null
                    ? (bool) $legacyGrant->granted && $defaultGranted
                    : $defaultGranted;

                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'permission' => $permission],
                    ['granted' => $granted, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        DB::table('role_permissions')->where('permission', 'chat.use')->delete();
    }
};
