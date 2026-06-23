<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Http\Responses\ApiResult;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends ApiController
{
    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', 'api')->with('permissions')->orderBy('name')->get();

        return ApiResult::success(RoleResource::collection($roles))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->with('permissions')->find($id);

        if (! $role) {
            return ApiResult::notFound('Rol no encontrado.')->toResponse();
        }

        return ApiResult::success(new RoleResource($role))->toResponse();
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->name, 'guard_name' => 'api']);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return ApiResult::created(new RoleResource($role->load('permissions')), 'Rol creado correctamente.')->toResponse();
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->find($id);

        if (! $role) {
            return ApiResult::notFound('Rol no encontrado.')->toResponse();
        }

        if ($request->filled('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return ApiResult::success(new RoleResource($role->load('permissions')), 'Rol actualizado correctamente.')->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->find($id);

        if (! $role) {
            return ApiResult::notFound('Rol no encontrado.')->toResponse();
        }

        $role->syncPermissions([]);
        $role->delete();

        return ApiResult::empty('Rol eliminado correctamente.')->toResponse();
    }
}
