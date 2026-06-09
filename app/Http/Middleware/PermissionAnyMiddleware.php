<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionAnyMiddleware
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        foreach ($permissions as $permissionValue) {
            $permission = Permission::tryFromString($permissionValue);

            if ($permission && $this->permissions->allows($user, $permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
