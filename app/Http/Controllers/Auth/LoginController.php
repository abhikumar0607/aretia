<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if (Toast::wantsJson()) {
                return Toast::jsonError('Invalid email or password.');
            }

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $request->session()->regenerate();

        return Toast::to(
            redirect()->intended($this->dashboardUrl(Auth::user()->role))->getTargetUrl(),
            'Welcome back!'
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function dashboardUrl(UserRole|string $role): string
    {
        if (is_string($role)) {
            $role = UserRole::from($role);
        }

        return route($role->dashboardRoute());
    }
}
