<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(UserRole::Client)) {
            return $next($request);
        }

        if ($user->isClientActive()) {
            return $next($request);
        }

        return redirect()->route('client.onboarding')
            ->with('info', 'Your account must be approved before placing orders.');
    }
}
