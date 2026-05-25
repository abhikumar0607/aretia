<?php

namespace App\Http\Middleware;

use App\Support\Toast;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts redirect+flash responses to JSON when the client expects a toast (AJAX forms).
 */
class JsonToastResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Toast::wantsJson() || $response instanceof JsonResponse) {
            return $response;
        }

        if ($response instanceof RedirectResponse) {
            $toast = $request->session()->get('toast');

            if (! $toast && $request->session()->has('success')) {
                $toast = Toast::payload($request->session()->get('success'));
            }

            if (! $toast) {
                $toast = Toast::payload('Saved successfully.');
            }

            return response()->json([
                'toast' => $toast,
                'redirect' => $response->getTargetUrl(),
            ]);
        }

        return $response;
    }
}
