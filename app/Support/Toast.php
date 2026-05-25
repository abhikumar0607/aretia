<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class Toast
{
    public const DURATION_MS = 5000;

    public static function wantsJson(): bool
    {
        return request()->ajax()
            || request()->wantsJson()
            || request()->header('X-Requested-With') === 'XMLHttpRequest'
            || str_contains(request()->header('Accept', ''), 'application/json');
    }

    public static function payload(string $message, string $type = 'success', ?string $title = null): array
    {
        return [
            'type' => $type,
            'title' => $title ?? match ($type) {
                'success' => 'Success',
                'error' => 'Error',
                'warning' => 'Warning',
                default => 'Notice',
            },
            'message' => $message,
            'duration' => self::DURATION_MS,
        ];
    }

    public static function back(string $message, string $type = 'success', ?string $title = null): JsonResponse|RedirectResponse
    {
        if (self::wantsJson()) {
            return response()->json([
                'toast' => self::payload($message, $type, $title),
                'redirect' => self::backUrl(),
            ]);
        }

        return back()->with('toast', self::payload($message, $type, $title));
    }

    /** Previous page URL — not the POST endpoint (fixes redirect to /cases/{id}/documents). */
    public static function backUrl(): string
    {
        $current = url()->current();
        $previous = url()->previous();

        if ($previous && self::pathOf($previous) !== self::pathOf($current)) {
            return $previous;
        }

        $referer = request()->headers->get('Referer');
        if ($referer && self::pathOf($referer) !== self::pathOf($current)) {
            return $referer;
        }

        return redirect()->back()->getTargetUrl();
    }

    private static function pathOf(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        return $path ? rtrim($path, '/') : '';
    }

    /**
     * @param  RedirectResponse|string  $redirect
     */
    public static function to(RedirectResponse|string $redirect, string $message, string $type = 'success', ?string $title = null): JsonResponse|RedirectResponse
    {
        $payload = self::payload($message, $type, $title);

        if (self::wantsJson()) {
            $url = $redirect instanceof RedirectResponse
                ? $redirect->getTargetUrl()
                : url($redirect);

            return response()->json([
                'toast' => $payload,
                'redirect' => $url,
            ]);
        }

        $response = $redirect instanceof RedirectResponse
            ? $redirect
            : redirect($redirect);

        return $response->with('toast', $payload);
    }

    public static function jsonError(string $message, int $status = 422, ?string $title = null): JsonResponse
    {
        return response()->json([
            'toast' => self::payload($message, 'error', $title ?? 'Error'),
        ], $status);
    }
}
