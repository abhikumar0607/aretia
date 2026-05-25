<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function clients(): View
    {
        $stats = [
            'client_companies' => Company::count(),
            'active_clients' => Company::where('status', CompanyStatus::Active)->count(),
            'client_users' => User::where('role', UserRole::Client)->count(),
        ];

        $companies = Company::with(['users' => fn ($q) => $q->where('role', UserRole::Client)->orderByDesc('is_primary')])
            ->latest()
            ->paginate(config('portal.per_page'));

        return view('admin.clients.index', compact('stats', 'companies'));
    }

    public function analysts(): View
    {
        $stats = [
            'analysts' => User::where('role', UserRole::Analyst)->count(),
        ];

        $analysts = User::where('role', UserRole::Analyst)
            ->withCount('assignedCases')
            ->orderBy('name')
            ->get();

        return view('admin.analysts.index', compact('stats', 'analysts'));
    }

    public function storeAnalyst(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $analyst = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => UserRole::Analyst,
        ]);

        $this->audit->log('analyst.created', $analyst, ['email' => $analyst->email]);

        return Toast::to(route('admin.analysts.index'), 'Analyst account created successfully.');
    }
}
