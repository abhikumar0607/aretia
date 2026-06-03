<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CaseController as AdminCaseController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\Admin\OnboardingController as AdminOnboardingController;
use App\Http\Controllers\Admin\BulkOrderController as AdminBulkOrderController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\WorkflowStageController;
use App\Http\Controllers\Analyst\CaseController as AnalystCaseController;
use App\Http\Controllers\Analyst\ReportController as AnalystReportController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
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
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\OnboardingController as SuperAdminOnboardingController;
use App\Http\Controllers\SuperAdmin\OrderController as SuperAdminOrderController;
use App\Http\Controllers\SuperAdmin\BulkOrderController as SuperAdminBulkOrderController;
use App\Http\Controllers\SuperAdmin\CaseController as SuperAdminCaseController;
use App\Http\Controllers\SuperAdmin\WorkflowStageController as SuperAdminWorkflowStageController;
use App\Http\Controllers\SuperAdmin\AuditLogController as SuperAdminAuditLogController;
use App\Http\Controllers\SuperAdmin\TeamController as SuperAdminTeamController;
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

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');
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
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::middleware('role:'.UserRole::SuperAdmin->value)->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/roles', fn () => view('superadmin.roles.index'))->name('roles.index');
    });

    Route::middleware('role:'.UserRole::Admin->value)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    });

    // Admin operational module
    Route::middleware('role:'.UserRole::Admin->value)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/onboarding', [AdminOnboardingController::class, 'index'])->name('onboarding.index');
        Route::get('/onboarding/{company}', [AdminOnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/{company}/approve', [AdminOnboardingController::class, 'approve'])->name('onboarding.approve');
        Route::post('/onboarding/{company}/reject', [AdminOnboardingController::class, 'reject'])->name('onboarding.reject');
        Route::get('/kyc/{kyc}/download', [AdminOnboardingController::class, 'downloadKyc'])->name('kyc.download');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/import', [AdminBulkOrderController::class, 'show'])->name('orders.import');
        Route::post('/orders/import', [AdminBulkOrderController::class, 'import'])->name('orders.import.store');
        Route::get('/orders/import/template', [AdminBulkOrderController::class, 'template'])->name('orders.import.template');
        Route::get('/orders/create', [AdminOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [AdminOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/documents', [AdminOrderController::class, 'storeDocument'])->name('orders.documents.store');
        Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{order}/reject', [AdminOrderController::class, 'reject'])->name('orders.reject');
        Route::get('/orders/{order}/documents/{document}/preview', [AdminOrderController::class, 'previewDocument'])->name('orders.documents.preview');
        Route::get('/orders/{order}/documents/{document}/download', [AdminOrderController::class, 'downloadDocument'])->name('orders.documents.download');
        Route::patch('/orders/{order}/due-date', [AdminOrderController::class, 'updateDueDate'])->name('orders.due-date');
        Route::get('/cases', [AdminCaseController::class, 'index'])->name('cases.index');
        Route::post('/cases/link-related', [AdminCaseController::class, 'linkRelated'])->name('cases.link');
        Route::get('/cases/{case}', [AdminCaseController::class, 'show'])->name('cases.show');
        Route::post('/cases/{case}/assign', [AdminCaseController::class, 'assign'])->name('cases.assign');
        Route::post('/cases/{case}/stage', [AdminCaseController::class, 'updateStage'])->name('cases.stage');
        Route::post('/cases/{case}/reports', [AnalystReportController::class, 'store'])->name('cases.reports.store');
        Route::get('/workflow', [WorkflowStageController::class, 'index'])->name('workflow.index');
        Route::post('/workflow', [WorkflowStageController::class, 'store'])->name('workflow.store');
        Route::patch('/workflow/{stage}/responsible', [WorkflowStageController::class, 'updateResponsible'])->name('workflow.responsible');
        Route::delete('/workflow/{stage}', [WorkflowStageController::class, 'destroy'])->name('workflow.destroy');
        Route::delete('/workflow/{stage}/delete', [WorkflowStageController::class, 'delete'])->name('workflow.delete');
        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('/clients', [AdminTeamController::class, 'clients'])->name('clients.index');
        Route::get('/clients/{company}', [AdminTeamController::class, 'showClient'])->name('clients.show');
        Route::post('/clients/{company}/deactivate', [AdminTeamController::class, 'deactivateCompany'])->name('clients.deactivate');
        Route::post('/clients/{company}/activate', [AdminTeamController::class, 'activateCompany'])->name('clients.activate');
        Route::post('/users/{user}/deactivate', [AdminTeamController::class, 'deactivateUser'])->name('users.deactivate');
        Route::post('/users/{user}/activate', [AdminTeamController::class, 'activateUser'])->name('users.activate');
        Route::delete('/users/{user}', [AdminTeamController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/employees', [AdminTeamController::class, 'employees'])->name('employees.index');
        Route::get('/employees/create', [AdminTeamController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees', [AdminTeamController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{user}/edit', [AdminTeamController::class, 'editEmployee'])->name('employees.edit');
        Route::patch('/employees/{user}', [AdminTeamController::class, 'updateEmployee'])->name('employees.update');
        Route::redirect('/analysts', '/admin/employees');
        Route::redirect('/team', '/admin/clients');
    });

    // Super Admin operational module (separate URLs + route names)
    Route::middleware('role:'.UserRole::SuperAdmin->value)->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/onboarding', [SuperAdminOnboardingController::class, 'index'])->name('onboarding.index');
        Route::get('/onboarding/{company}', [SuperAdminOnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/{company}/approve', [SuperAdminOnboardingController::class, 'approve'])->name('onboarding.approve');
        Route::post('/onboarding/{company}/reject', [SuperAdminOnboardingController::class, 'reject'])->name('onboarding.reject');
        Route::get('/kyc/{kyc}/download', [SuperAdminOnboardingController::class, 'downloadKyc'])->name('kyc.download');
        Route::get('/orders', [SuperAdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/import', [SuperAdminBulkOrderController::class, 'show'])->name('orders.import');
        Route::post('/orders/import', [SuperAdminBulkOrderController::class, 'import'])->name('orders.import.store');
        Route::get('/orders/import/template', [SuperAdminBulkOrderController::class, 'template'])->name('orders.import.template');
        Route::get('/orders/create', [SuperAdminOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [SuperAdminOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [SuperAdminOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/documents', [SuperAdminOrderController::class, 'storeDocument'])->name('orders.documents.store');
        Route::post('/orders/{order}/approve', [SuperAdminOrderController::class, 'approve'])->name('orders.approve');
        Route::post('/orders/{order}/reject', [SuperAdminOrderController::class, 'reject'])->name('orders.reject');
        Route::get('/orders/{order}/documents/{document}/preview', [SuperAdminOrderController::class, 'previewDocument'])->name('orders.documents.preview');
        Route::get('/orders/{order}/documents/{document}/download', [SuperAdminOrderController::class, 'downloadDocument'])->name('orders.documents.download');
        Route::patch('/orders/{order}/due-date', [SuperAdminOrderController::class, 'updateDueDate'])->name('orders.due-date');
        Route::get('/cases', [SuperAdminCaseController::class, 'index'])->name('cases.index');
        Route::post('/cases/link-related', [SuperAdminCaseController::class, 'linkRelated'])->name('cases.link');
        Route::get('/cases/{case}', [SuperAdminCaseController::class, 'show'])->name('cases.show');
        Route::post('/cases/{case}/assign', [SuperAdminCaseController::class, 'assign'])->name('cases.assign');
        Route::post('/cases/{case}/stage', [SuperAdminCaseController::class, 'updateStage'])->name('cases.stage');
        Route::post('/cases/{case}/reports', [AnalystReportController::class, 'store'])->name('cases.reports.store');
        Route::get('/workflow', [SuperAdminWorkflowStageController::class, 'index'])->name('workflow.index');
        Route::post('/workflow', [SuperAdminWorkflowStageController::class, 'store'])->name('workflow.store');
        Route::patch('/workflow/{stage}/responsible', [SuperAdminWorkflowStageController::class, 'updateResponsible'])->name('workflow.responsible');
        Route::delete('/workflow/{stage}', [SuperAdminWorkflowStageController::class, 'destroy'])->name('workflow.destroy');
        Route::delete('/workflow/{stage}/delete', [SuperAdminWorkflowStageController::class, 'delete'])->name('workflow.delete');
        Route::get('/audit', [SuperAdminAuditLogController::class, 'index'])->name('audit.index');
        Route::get('/clients', [SuperAdminTeamController::class, 'clients'])->name('clients.index');
        Route::get('/clients/{company}', [SuperAdminTeamController::class, 'showClient'])->name('clients.show');
        Route::post('/clients/{company}/deactivate', [SuperAdminTeamController::class, 'deactivateCompany'])->name('clients.deactivate');
        Route::post('/clients/{company}/activate', [SuperAdminTeamController::class, 'activateCompany'])->name('clients.activate');
        Route::post('/users/{user}/deactivate', [SuperAdminTeamController::class, 'deactivateUser'])->name('users.deactivate');
        Route::post('/users/{user}/activate', [SuperAdminTeamController::class, 'activateUser'])->name('users.activate');
        Route::delete('/users/{user}', [SuperAdminTeamController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/employees', [SuperAdminTeamController::class, 'employees'])->name('employees.index');
        Route::get('/employees/create', [SuperAdminTeamController::class, 'createEmployee'])->name('employees.create');
        Route::post('/employees', [SuperAdminTeamController::class, 'storeEmployee'])->name('employees.store');
        Route::get('/employees/{user}/edit', [SuperAdminTeamController::class, 'editEmployee'])->name('employees.edit');
        Route::patch('/employees/{user}', [SuperAdminTeamController::class, 'updateEmployee'])->name('employees.update');
    });

    Route::middleware(['role:'.UserRole::Client->value, 'client.onboarded'])->prefix('client')->name('client.')->group(function () {
        Route::get('/onboarding', [ClientOnboardingController::class, 'show'])->name('onboarding');
        Route::get('/onboarding/account', [ClientOnboardingController::class, 'account'])->name('onboarding.account');
        Route::put('/onboarding/account', [ClientOnboardingController::class, 'updateAccount'])->name('onboarding.account.update');
        Route::post('/onboarding/upload', [ClientOnboardingController::class, 'store'])->name('onboarding.store');
        Route::post('/onboarding/submit', [ClientOnboardingController::class, 'submit'])->name('onboarding.submit');
        Route::post('/onboarding/reopen', [ClientOnboardingController::class, 'reopen'])->name('onboarding.reopen');
        Route::get('/onboarding/documents/{kyc}', [ClientOnboardingController::class, 'document'])->name('onboarding.document');

        Route::middleware('company.active')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'client'])->name('dashboard');
            Route::get('/orders', [ClientOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/import', [ClientBulkOrderController::class, 'show'])->name('orders.import');
            Route::post('/orders/import', [ClientBulkOrderController::class, 'import'])->name('orders.import.store');
            Route::get('/orders/import/template', [ClientBulkOrderController::class, 'template'])->name('orders.import.template');
            Route::get('/orders/create', [ClientOrderController::class, 'create'])->name('orders.create');
            Route::post('/orders', [ClientOrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/{order}', [ClientOrderController::class, 'show'])->name('orders.show');
            Route::patch('/orders/{order}/due-date', [ClientOrderController::class, 'updateDueDate'])->name('orders.due-date');
            Route::post('/orders/{order}/documents', [ClientOrderController::class, 'storeDocument'])->name('orders.documents.store');
            Route::get('/orders/{order}/documents/{document}/preview', [ClientOrderController::class, 'previewDocument'])->name('orders.documents.preview');
            Route::get('/orders/{order}/documents/{document}/download', [ClientOrderController::class, 'downloadDocument'])->name('orders.documents.download');
            Route::get('/cases', [ClientCaseController::class, 'index'])->name('cases.index');
            Route::post('/cases/link-related', [ClientCaseController::class, 'linkRelated'])->name('cases.link');
            Route::get('/cases/{case}', [ClientCaseController::class, 'show'])->name('cases.show');
            Route::get('/reports', [ClientReportController::class, 'index'])->name('reports.index');
            Route::get('/reports/{report}', [ClientReportController::class, 'show'])->name('reports.show');
            Route::post('/reports/{report}/download', [ClientReportController::class, 'download'])->name('reports.download');
        });
    });

    foreach (UserRole::employeeRoles() as $employeeRole) {
        Route::middleware('role:'.$employeeRole->value)
            ->prefix($employeeRole->value)
            ->name($employeeRole->value.'.')
            ->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'employee'])->name('dashboard');
                Route::get('/cases', [AnalystCaseController::class, 'index'])->name('cases.index');
                Route::get('/cases/{case}', [AnalystCaseController::class, 'show'])->name('cases.show');
                Route::post('/cases/{case}/stage', [AnalystCaseController::class, 'updateStage'])->name('cases.stage');
                Route::post('/cases/{case}/reports', [AnalystReportController::class, 'store'])->name('reports.store');
            });
    }
});
