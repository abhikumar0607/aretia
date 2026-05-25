<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BulkOrderController as AdminBulkOrderController;
use App\Http\Controllers\Admin\CaseController as AdminCaseController;
use App\Http\Controllers\Admin\OnboardingController as AdminOnboardingController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\WorkflowStageController;
use App\Http\Controllers\Analyst\CaseController as AnalystCaseController;
use App\Http\Controllers\Analyst\ReportController as AnalystReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Client\BulkOrderController as ClientBulkOrderController;
use App\Http\Controllers\Client\CaseController as ClientCaseController;
use App\Http\Controllers\Client\OnboardingController as ClientOnboardingController;
use App\Http\Controllers\Client\OrderController as ClientOrderController;
use App\Http\Controllers\Client\RegisterController;
use App\Http\Controllers\Client\ReportController as ClientReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatInboxController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shared\DocumentController;
use App\Http\Controllers\Shared\MessageController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route(Auth::user()->role->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/chat/inbox', [ChatInboxController::class, 'index'])->name('chat.inbox.index');
    Route::post('/chat/inbox/read-all', [ChatInboxController::class, 'markAllRead'])->name('chat.inbox.read-all');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/cases/{case}/messages', [MessageController::class, 'index'])->name('cases.messages.index');
    Route::post('/cases/{case}/messages', [MessageController::class, 'store'])->name('cases.messages.store');
    Route::post('/cases/{case}/messages/read', [MessageController::class, 'markRead'])->name('cases.messages.read');
    Route::post('/cases/{case}/documents', [DocumentController::class, 'store'])->name('cases.documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    $staffRoles = UserRole::SuperAdmin->value.','.UserRole::Admin->value;

    Route::middleware('role:'.UserRole::SuperAdmin->value)->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'superadmin'])->name('dashboard');
    });

    Route::middleware('role:'.UserRole::Admin->value)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    });

    Route::middleware('role:'.$staffRoles)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/onboarding', [AdminOnboardingController::class, 'index'])->name('onboarding.index');
        Route::get('/onboarding/{company}', [AdminOnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/{company}/approve', [AdminOnboardingController::class, 'approve'])->name('onboarding.approve');
        Route::post('/onboarding/{company}/reject', [AdminOnboardingController::class, 'reject'])->name('onboarding.reject');
        Route::get('/kyc/{kyc}/download', [AdminOnboardingController::class, 'downloadKyc'])->name('kyc.download');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/import', [AdminBulkOrderController::class, 'show'])->name('orders.import');
        Route::post('/orders/import', [AdminBulkOrderController::class, 'import'])->name('orders.import.store');
        Route::get('/orders/import/template', [AdminBulkOrderController::class, 'template'])->name('orders.import.template');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/cases', [AdminCaseController::class, 'index'])->name('cases.index');
        Route::get('/cases/{case}', [AdminCaseController::class, 'show'])->name('cases.show');
        Route::post('/cases/{case}/assign', [AdminCaseController::class, 'assign'])->name('cases.assign');
        Route::post('/cases/{case}/stage', [AdminCaseController::class, 'updateStage'])->name('cases.stage');
        Route::get('/workflow', [WorkflowStageController::class, 'index'])->name('workflow.index');
        Route::post('/workflow', [WorkflowStageController::class, 'store'])->name('workflow.store');
        Route::delete('/workflow/{stage}', [WorkflowStageController::class, 'destroy'])->name('workflow.destroy');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    });

    Route::middleware(['role:'.UserRole::Client->value, 'client.onboarded'])->prefix('client')->name('client.')->group(function () {
        Route::get('/onboarding', [ClientOnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding', [ClientOnboardingController::class, 'store'])->name('onboarding.store');

        Route::middleware('company.active')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'client'])->name('dashboard');
            Route::get('/orders', [ClientOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/import', [ClientBulkOrderController::class, 'show'])->name('orders.import');
            Route::post('/orders/import', [ClientBulkOrderController::class, 'import'])->name('orders.import.store');
            Route::get('/orders/import/template', [ClientBulkOrderController::class, 'template'])->name('orders.import.template');
            Route::get('/orders/create', [ClientOrderController::class, 'create'])->name('orders.create');
            Route::post('/orders', [ClientOrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/{order}', [ClientOrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/documents', [ClientOrderController::class, 'storeDocument'])->name('orders.documents.store');
            Route::get('/orders/{order}/documents/{document}/download', [ClientOrderController::class, 'downloadDocument'])->name('orders.documents.download');
            Route::get('/cases', [ClientCaseController::class, 'index'])->name('cases.index');
            Route::get('/cases/{case}', [ClientCaseController::class, 'show'])->name('cases.show');
            Route::get('/reports', [ClientReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{report}', [ClientReportController::class, 'show'])->name('reports.show');
            Route::post('/reports/{report}/download', [ClientReportController::class, 'download'])->name('reports.download');
        });
    });

    Route::middleware('role:'.UserRole::Analyst->value)->prefix('analyst')->name('analyst.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'analyst'])->name('dashboard');
        Route::get('/cases', [AnalystCaseController::class, 'index'])->name('cases.index');
        Route::get('/cases/{case}', [AnalystCaseController::class, 'show'])->name('cases.show');
        Route::post('/cases/{case}/stage', [AnalystCaseController::class, 'updateStage'])->name('cases.stage');
        Route::post('/cases/{case}/reports', [AnalystReportController::class, 'store'])->name('reports.store');
    });
});
