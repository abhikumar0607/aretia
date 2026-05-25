<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_primary')->default(false)->after('phone');
            $table->string('onboarding_status')->default('registered')->after('is_primary');
        });

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('original_name');
            $table->string('path');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('due_days')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_package_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('subject_type')->nullable();
            $table->string('subject_name')->nullable();
            $table->text('subject_details')->nullable();
            $table->text('custom_request')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('original_name');
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#64748b');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('case_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('uploaded');
            $table->string('category')->nullable();
            $table->string('original_name');
            $table->string('path');
            $table->boolean('is_encrypted')->default(false);
            $table->string('password_hint')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->boolean('is_password_protected')->default(false);
            $table->string('file_password')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('case_stage_histories');
        Schema::dropIfExists('cases');
        Schema::dropIfExists('workflow_stages');
        Schema::dropIfExists('order_documents');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('service_packages');
        Schema::dropIfExists('kyc_documents');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['phone', 'is_primary', 'onboarding_status']);
        });
        Schema::dropIfExists('companies');
    }
};
