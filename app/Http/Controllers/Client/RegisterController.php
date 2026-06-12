<?php

namespace App\Http\Controllers\Client;

use App\Enums\CompanyStatus;
use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Rules\StrictEmail;
use App\Services\AuditService;
use App\Services\RegistrationOtpService;
use App\Support\PasswordRules;
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
    public function __construct(
        private AuditService $audit,
        private RegistrationOtpService $otp,
    ) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function showVerify(): View|RedirectResponse
    {
        $pending = $this->otp->pending();

        if ($pending === null) {
            return redirect()->route('register');
        }

        return view('auth.register-verify', [
            'maskedEmail' => RegistrationOtpService::maskEmail($pending['email']),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', new StrictEmail, 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $this->otp->storePending($data);

        return Toast::to(
            route('register.verify'),
            'Verification code sent to your email. Enter the OTP to continue.'
        );
    }

    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        if ($this->otp->pending() === null) {
            return redirect()->route('register');
        }

        $request->validate([
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        if (! $this->otp->verify($request->input('otp'))) {
            return Toast::back('OTP not match', 'error');
        }

        $data = $this->otp->pending();
        $this->otp->clear();

        $user = $this->createAccount($data);

        event(new Registered($user));
        Auth::login($user);

        return Toast::to(route('client.onboarding'), 'Email verified. Please upload KYC documents.');
    }

    public function resendOtp(): JsonResponse|RedirectResponse
    {
        if (! $this->otp->resend()) {
            return redirect()->route('register');
        }

        return Toast::to(route('register.verify'), 'A new verification code has been sent to your email.');
    }

    /**
     * @param  array{name: string, company: string, email: string, phone: string, password: string}  $data
     */
    private function createAccount(array $data): User
    {
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

        return $user;
    }
}
