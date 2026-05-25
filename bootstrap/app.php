<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'client.onboarded' => \App\Http\Middleware\EnsureClientOnboarded::class,
            'company.active' => \App\Http\Middleware\EnsureCompanyActive::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\JsonToastResponse::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function () {
            $role = auth()->user()->role;

            if ($role instanceof \App\Enums\UserRole) {
                return route($role->dashboardRoute());
            }

            return route('client.dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $ajaxToast = fn ($request) => $request->ajax()
            || $request->wantsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) use ($ajaxToast) {
            if ($ajaxToast($request)) {
                return response()->json([
                    'toast' => \App\Support\Toast::payload(
                        $e->validator->errors()->first(),
                        'error',
                        'Validation failed'
                    ),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) use ($ajaxToast) {
            if ($ajaxToast($request) && $e->getStatusCode() >= 400) {
                return response()->json([
                    'toast' => \App\Support\Toast::payload(
                        $e->getMessage() ?: 'Request failed.',
                        'error',
                        'Error'
                    ),
                ], $e->getStatusCode());
            }
        });
    })->create();
