<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return $this->successResponse([
            'roles'       => Role::with('permissions')->get(),
            'permissions' => Permission::all(),
        ], 'Roles y permisos del sistema');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return $this->successResponse($role->load('permissions'), 'Rol creado correctamente', 201);
    }

    public function assignPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions']);
        return $this->successResponse($role->load('permissions'), 'Permisos asignados correctamente');
    }
}