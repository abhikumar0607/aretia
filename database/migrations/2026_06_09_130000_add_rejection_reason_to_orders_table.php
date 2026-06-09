<?php

use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('confirmed_at');
        });

        Order::query()
            ->where('status', 'rejected')
            ->whereNull('rejection_reason')
            ->pluck('id')
            ->each(function (int $orderId) {
                $reason = AuditLog::query()
                    ->where('auditable_type', Order::class)
                    ->where('auditable_id', $orderId)
                    ->where('action', 'order.rejected')
                    ->latest('id')
                    ->value('properties');

                if (! is_array($reason) || empty($reason['reason'])) {
                    return;
                }

                Order::query()
                    ->whereKey($orderId)
                    ->update(['rejection_reason' => $reason['reason']]);
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
