<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Support\Toast;
use App\Enums\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function __construct(private PermissionService $permissions) {}

    public function index(): View
    {
        return view('superadmin.roles.index', [
            'groupedPermissions' => Permission::groupedForAdmin(),
            'displayRoles' => PermissionService::matrixDisplayRoles(),
            'staffRoles' => PermissionService::configurableStaffRoles(),
            'grants' => $this->permissions->allGrants(),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $this->permissions->syncMatrix($request->input('permissions', []));

        return Toast::back('Permissions updated.');
    }
}
