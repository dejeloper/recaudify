<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends ApiController
{
    public function index(): JsonResponse
    {
        return response()->json(Permission::where('guard_name', 'api')->get());
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Permission::where('guard_name', 'api')->findOrFail($id));
    }

    public function store(): JsonResponse
    {
        request()->validate([
            'name' => ['required', 'string', 'unique:permissions,name'],
        ]);

        $permission = Permission::create(['name' => request()->name, 'guard_name' => 'api']);

        return response()->json($permission, 201);
    }

    public function update(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->findOrFail($id);

        request()->validate([
            'name' => ['required', 'string', 'unique:permissions,name,' . $id],
        ]);

        $permission->update(['name' => request()->name]);

        return response()->json($permission);
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permiso eliminado correctamente.']);
    }
}
