<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Responses\ApiResult;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends ApiController
{
    public function index(): JsonResponse
    {
        $permissions = Permission::where('guard_name', 'api')->orderBy('name')->get();

        return ApiResult::success(PermissionResource::collection($permissions))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->find($id);

        if (! $permission) {
            return ApiResult::notFound('Permiso no encontrado.')->toResponse();
        }

        return ApiResult::success(new PermissionResource($permission))->toResponse();
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create(['name' => $request->name, 'guard_name' => 'api']);

        return ApiResult::created(new PermissionResource($permission), 'Permiso creado correctamente.')->toResponse();
    }

    public function update(UpdatePermissionRequest $request, int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->find($id);

        if (! $permission) {
            return ApiResult::notFound('Permiso no encontrado.')->toResponse();
        }

        $permission->update(['name' => $request->name]);

        return ApiResult::success(new PermissionResource($permission), 'Permiso actualizado correctamente.')->toResponse();
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

    public function trashed(): JsonResponse
    {
        $permissions = Permission::onlyTrashed()
            ->where('guard_name', 'api')
            ->orderBy('name')
            ->get();

        return ApiResult::success(PermissionResource::collection($permissions))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $permission = Permission::onlyTrashed()->where('guard_name', 'api')->find($id);

        if (! $permission) {
            return ApiResult::notFound('Permiso no encontrado.')->toResponse();
        }

        $permission->restore();

        return ApiResult::empty('Permiso restaurado correctamente.')->toResponse();
    }
}
