<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Notifications\EmployeeAccountCreatedNotification;
use App\Rules\StrictEmail;
use App\Services\AuditService;
use App\Services\UserAccessService;
use App\Support\CompanyFilter;
use App\Support\PasswordRules;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(
        private AuditService $audit,
        private UserAccessService $access,
    ) {}

    public function clients(Request $request): View
    {
        $stats = [
            'client_companies' => Company::count(),
            'active_clients' => Company::where('status', CompanyStatus::Active)->count(),
            'client_users' => User::where('role', UserRole::Client)->count(),
        ];

        $search = trim((string) $request->input('q'));
        $companyFilter = $request->input('company');

        $clientUsers = User::query()
            ->where('role', UserRole::Client)
            ->with('company')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('company', function ($company) use ($search) {
                            $company->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            });

        CompanyFilter::apply($clientUsers, $request);

        $clientUsers = $clientUsers
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $manageableUserIds = $clientUsers->getCollection()
            ->filter(fn (User $user) => $this->access->canManage($request->user(), $user))
            ->pluck('id')
            ->all();

        $hasFilters = $search !== ''
            || (is_string($companyFilter) && $companyFilter !== '');

        return view('superadmin.clients.index', [
            'stats' => $stats,
            'clientUsers' => $clientUsers,
            'companyOptions' => CompanyFilter::options(),
            'manageableUserIds' => $manageableUserIds,
            'hasFilters' => $hasFilters,
        ]);
    }

    public function showClient(Request $request, Company $company): View
    {
        $company->load(['users' => fn ($q) => $q->where('role', UserRole::Client)->orderByDesc('is_primary')]);

        $manageableUserIds = $company->users
            ->filter(fn (User $user) => $this->access->canManage($request->user(), $user))
            ->pluck('id')
            ->all();

        return view('superadmin.clients.show', compact('company', 'manageableUserIds'));
    }

    public function employees(Request $request): View
    {
        $stats = [
            'employees' => User::employees()->count(),
        ];

        $search = trim((string) $request->input('q'));
        $roleFilter = $request->input('role');

        $employees = User::query()
            ->employees()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(
                is_string($roleFilter) && $roleFilter !== '' && UserRole::tryFrom($roleFilter)?->isEmployeeRole(),
                fn ($query) => $query->where('role', $roleFilter),
            )
            ->withCount('assignedCases')
            ->orderBy('name')
            ->paginate(config('portal.per_page'))
            ->withQueryString();

        $manageableUserIds = $employees->getCollection()
            ->filter(fn (User $user) => $this->access->canManage($request->user(), $user))
            ->pluck('id')
            ->all();

        $hasFilters = $search !== ''
            || (is_string($roleFilter) && $roleFilter !== '' && UserRole::tryFrom($roleFilter)?->isEmployeeRole());

        return view('superadmin.employees.index', [
            'stats' => $stats,
            'employees' => $employees,
            'manageableUserIds' => $manageableUserIds,
            'roleOptions' => UserRole::employeeRoleOptions(),
            'hasFilters' => $hasFilters,
        ]);
    }

    public function createEmployee(): View
    {
        return view('superadmin.employees.create');
    }

    public function editEmployee(Request $request, User $user): View
    {
        if (! $user->isEmployee()) {
            abort(404);
        }

        if (! $this->access->canManage($request->user(), $user)) {
            abort(403, 'You do not have permission to manage this account.');
        }

        return view('superadmin.employees.edit', ['employee' => $user]);
    }

    public function storeEmployee(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', new StrictEmail, 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $role = UserRole::from($data['role']);
        if (! $role->isEmployeeRole()) {
            return Toast::back('Role must be Analyst, QA, or FQA.');
        }

        $plainPassword = $data['password'];

        $employee = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($plainPassword),
            'role' => $role,
            'is_active' => true,
        ]);

        $employee->notify(new EmployeeAccountCreatedNotification($plainPassword));

        $this->audit->log('employee.created', $employee, [
            'email' => $employee->email,
            'role' => $employee->role->value,
        ]);

        return Toast::to(route('superadmin.employees.index'), 'Employee created. Login details sent to their email.');
    }

    public function updateEmployee(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $user->isEmployee()) {
            abort(404);
        }

        if (! $this->access->canManage($request->user(), $user)) {
            abort(403, 'You do not have permission to manage this account.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', new StrictEmail, Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        $role = UserRole::from($data['role']);
        if (! $role->isEmployeeRole()) {
            return Toast::back('Role must be Analyst, QA, or FQA.');
        }

        $previousRole = $user->role->value;

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $role,
        ]);

        $this->audit->log('employee.updated', $user, [
            'email' => $user->email,
            'role' => $user->role->value,
            'previous_role' => $previousRole,
        ]);

        return Toast::to(route('superadmin.employees.index'), 'Employee updated successfully.');
    }

    public function deactivateUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->access->deactivateUser($request->user(), $user);

        return Toast::back('Account deactivated. They can no longer sign in.');
    }

    public function activateUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $this->access->activateUser($request->user(), $user);

        return Toast::back('Account restored. They can sign in again.');
    }

    public function destroyUser(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $name = $user->name;

        $this->access->deleteUser($request->user(), $user);

        return Toast::back("{$name}'s account was deleted permanently.");
    }

    public function deactivateCompany(Request $request, Company $company): JsonResponse|RedirectResponse
    {
        $this->access->deactivateCompany($request->user(), $company);

        return Toast::to(
            route('superadmin.clients.show', $company),
            'Company suspended. All users under this company have been signed out.'
        );
    }

    public function activateCompany(Request $request, Company $company): JsonResponse|RedirectResponse
    {
        $this->access->activateCompany($request->user(), $company);

        return Toast::to(
            route('superadmin.clients.show', $company),
            'Company access restored. Users can sign in again.'
        );
    }
}

