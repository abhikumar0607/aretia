<?php

namespace App\Http\Controllers\Client;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Toast;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $company = Company::create([
            'name' => $data['company'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => CompanyStatus::Pending,
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::Client,
            'company_id' => $company->id,
            'is_primary' => true,
            'onboarding_status' => OnboardingStatus::Registered,
        ]);

        $this->audit->log('client.registered', $company, ['user_id' => $user->id]);

        event(new Registered($user));
        Auth::login($user);

        return Toast::to(route('client.onboarding'), 'Registration successful. Please upload KYC documents.');
    }
}
