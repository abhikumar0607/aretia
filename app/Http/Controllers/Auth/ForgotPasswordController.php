<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return Toast::to(route('password.request'), 'Password reset link sent. Please check your inbox.');
        }

        $message = match ($status) {
            Password::INVALID_USER => 'We could not find a user with that email address.',
            Password::RESET_THROTTLED => 'Please wait a moment before requesting another reset link.',
            default => 'Unable to send reset link. Please try again.',
        };

        if (Toast::wantsJson()) {
            return Toast::jsonError($message);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }
}
