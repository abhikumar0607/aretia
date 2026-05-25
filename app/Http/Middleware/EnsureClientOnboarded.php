<?php

namespace App\Http\Middleware;

use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(UserRole::Client)) {
            return $next($request);
        }

        if ($user->onboarding_status === OnboardingStatus::Active) {
            return $next($request);
        }

        $allowed = ['client.onboarding', 'client.onboarding.store', 'logout'];

        if (in_array($request->route()?->getName(), $allowed, true)) {
            return $next($request);
        }

        return redirect()->route('client.onboarding')
            ->with('info', 'Please complete onboarding before continuing.');
    }
}
