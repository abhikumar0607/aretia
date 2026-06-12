<?php

namespace App\Http\Middleware;

use App\Enums\OnboardingStatus;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientOnboarded
{
    /** @var list<string> */
    private const ONBOARDING_ROUTES = [
        'client.onboarding',
        'client.onboarding.account',
        'client.onboarding.account.update',
        'client.onboarding.store',
        'client.onboarding.submit',
        'client.onboarding.reopen',
        'client.onboarding.document',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(UserRole::Client)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($user->onboarding_status === OnboardingStatus::Active) {
            if (in_array($routeName, self::ONBOARDING_ROUTES, true)) {
                return redirect()->route('client.dashboard');
            }

            return $next($request);
        }

        $allowed = [
            ...self::ONBOARDING_ROUTES,
            'logout',
        ];

        if (in_array($routeName, $allowed, true)) {
            return $next($request);
        }

        return redirect()->route('client.onboarding')
            ->with('info', 'Please complete onboarding before continuing.');
    }
}
