<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        if ($userRole instanceof UserRole) {
            $userRole = $userRole->value;
        }

        if (! in_array($userRole, $roles, true)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
