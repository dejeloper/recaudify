<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResult;
use Illuminate\Http\JsonResponse;
use App\Models\Permission;

class PermissionController extends ApiController
{
    public function index(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'api')->orderBy('name')->get();

        return ApiResult::success($permissions)->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->find($id);

        if (! $permission) {
            return ApiResult::notFound('Permiso no encontrado.')->toResponse();
        }

        return ApiResult::success($permission)->toResponse();
    }

    public function store(): JsonResponse
    {
        request()->validate([
            'name' => ['required', 'string', 'max:100', 'unique:permissions,name', 'regex:/^[a-z_]+\.[a-z_]+$/'],
        ]);

        $permission = Permission::create(['name' => request()->name, 'guard_name' => 'api']);

        return ApiResult::created($permission, 'Permiso creado correctamente.')->toResponse();
    }

    public function update(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->find($id);

        if (! $permission) {
            return ApiResult::notFound('Permiso no encontrado.')->toResponse();
        }

        request()->validate([
            'name' => ['required', 'string', 'max:100', 'unique:permissions,name,' . $id, 'regex:/^[a-z_]+\.[a-z_]+$/'],
        ]);

        $permission->update(['name' => request()->name]);

        return ApiResult::success($permission, 'Permiso actualizado correctamente.')->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->find($id);

        if (! $permission) {
            return ApiResult::notFound('Permiso no encontrado.')->toResponse();
        }

        $permission->delete();

        return ApiResult::empty('Permiso eliminado correctamente.')->toResponse();
    }
}
