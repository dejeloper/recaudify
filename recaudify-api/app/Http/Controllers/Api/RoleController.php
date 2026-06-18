<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json(Role::where('guard_name', 'api')->with('permissions')->get());
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->with('permissions')->findOrFail($id);

        return response()->json($role);
    }

    public function store(): JsonResponse
    {
        request()->validate([
            'name'          => ['required', 'string', 'unique:roles,name'],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => request()->name, 'guard_name' => 'api']);

        if (request()->filled('permissions')) {
            $role->syncPermissions(request()->permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->findOrFail($id);

        request()->validate([
            'name'          => ['sometimes', 'string', 'unique:roles,name,' . $id],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (request()->filled('name')) {
            $role->update(['name' => request()->name]);
        }

        if (request()->has('permissions')) {
            $role->syncPermissions(request()->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->findOrFail($id);
        $role->syncPermissions([]);
        $role->delete();

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }
}
