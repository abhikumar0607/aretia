<?php

namespace App\Enums;

enum Permission: string
{
    case OnboardingApprove = 'onboarding.approve';
    case OrdersCreate = 'orders.create';
    case OrdersApprove = 'orders.approve';
    case CasesManage = 'cases.manage';
    case ReportsView = 'reports.view';
    case ReportsManage = 'reports.manage';
    case WorkflowManage = 'workflow.manage';
    case ClientsView = 'clients.view';
    case ClientsManage = 'clients.manage';
    case EmployeesView = 'employees.view';
    case EmployeesManage = 'employees.manage';
    case PermissionsManage = 'permissions.manage';
    case AuditView = 'audit.view';
    case ProfileEdit = 'profile.edit';
    case ChatClient = 'chat.client';

    public function label(): string
    {
        return match ($this) {
            self::ProfileEdit => 'Edit profile',
            self::ChatClient => 'Case chat',
            self::OnboardingApprove => 'Approve onboarding',
            self::OrdersCreate => 'Create orders',
            self::OrdersApprove => 'Approve orders',
            self::CasesManage => 'Manage cases',
            self::ReportsView => 'View reports',
            self::ReportsManage => 'Work on reports',
            self::WorkflowManage => 'Manage workflow',
            self::ClientsView => 'View clients',
            self::ClientsManage => 'Manage clients',
            self::EmployeesView => 'View employees',
            self::EmployeesManage => 'Manage employees',
            self::PermissionsManage => 'Manage permissions',
            self::AuditView => 'View audit trail',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ProfileEdit => 'Update own name, phone, photo, and password.',
            self::ChatClient => 'Open the shared case chat thread. Client, Admin & Super Admin: any case. Analyst, QA & FQA: only when assigned to that case.',
            self::OnboardingApprove => 'Review onboarding queue, approve or reject companies, download KYC.',
            self::OrdersCreate => 'Create orders and bulk import on behalf of clients.',
            self::OrdersApprove => 'Approve or reject pending orders and create cases.',
            self::CasesManage => 'View cases, assign teams, and override workflow stages.',
            self::ReportsView => 'View and download delivered final reports (scoped to company or assigned cases).',
            self::ReportsManage => 'Upload and deliver final reports to clients.',
            self::WorkflowManage => 'Add, edit, and deactivate workflow stages.',
            self::ClientsView => 'View client companies and their users.',
            self::ClientsManage => 'Suspend companies and activate, deactivate, or delete client users.',
            self::EmployeesView => 'View internal employees (Analyst, QA, FQA).',
            self::EmployeesManage => 'Create, edit, and activate or deactivate employees.',
            self::PermissionsManage => 'Access Permissions & Roles settings (Admin only if granted).',
            self::AuditView => 'Open the audit trail and view platform activity logs.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ProfileEdit, self::ChatClient => 'Portal & account',
            self::OnboardingApprove => 'Onboarding',
            self::OrdersCreate, self::OrdersApprove => 'Orders',
            self::CasesManage => 'Cases',
            self::ReportsView, self::ReportsManage => 'Reports',
            self::WorkflowManage => 'Workflow',
            self::ClientsView, self::ClientsManage => 'Clients',
            self::EmployeesView, self::EmployeesManage => 'Employees',
            self::PermissionsManage, self::AuditView => 'System',
        };
    }

    public function isUniversal(): bool
    {
        return match ($this) {
            self::ProfileEdit, self::ChatClient, self::ReportsView, self::ReportsManage => true,
            default => false,
        };
    }

    /** @return list<self> */
    public static function configurableForAdmin(): array
    {
        return self::cases();
    }

    /** @return array<string, list<self>> */
    public static function groupedForAdmin(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->group()][] = $permission;
        }

        if (isset($groups['Portal & account'])) {
            $portal = $groups['Portal & account'];
            unset($groups['Portal & account']);
            $groups['Portal & account'] = $portal;
        }

        return $groups;
    }

    public static function tryFromString(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
