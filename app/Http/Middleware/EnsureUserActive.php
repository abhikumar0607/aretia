<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $blocked = ! $user->is_active
            || ($user->hasRole(UserRole::Client) && $user->company?->status === CompanyStatus::Suspended);

        if (! $blocked) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->withErrors(['email' => 'Your account access has been removed. Contact support if you need help.']);
    }
}
