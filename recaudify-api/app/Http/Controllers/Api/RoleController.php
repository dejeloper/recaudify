<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Http\Responses\ApiResult;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

class RoleController extends ApiController
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(RoleResource::collection($this->roleService->all()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->find($id);

        if (!$role) {
            return ApiResult::notFound("Rol no encontrado.")->toResponse();
        }

        return ApiResult::success(new RoleResource($role))->toResponse();
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create(
            $request->name,
            $request->filled("permissions") ? $request->permissions : [],
        );

        return ApiResult::created(new RoleResource($role), "Rol creado correctamente.")->toResponse();
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->roleService->find($id);

        if (!$role) {
            return ApiResult::notFound("Rol no encontrado.")->toResponse();
        }

        $updated = $this->roleService->update(
            $role,
            $request->filled("name") ? $request->name : null,
            $request->has("permissions") ? $request->permissions : null,
        );

        return ApiResult::success(new RoleResource($updated), "Rol actualizado correctamente.")->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleService->find($id);

        if (!$role) {
            return ApiResult::notFound("Rol no encontrado.")->toResponse();
        }

        $this->roleService->delete($role);

        return ApiResult::empty("Rol eliminado correctamente.")->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(RoleResource::collection($this->roleService->trashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $role = $this->roleService->findTrashed($id);

        if (!$role) {
            return ApiResult::notFound("Rol no encontrado.")->toResponse();
        }

        $this->roleService->restore($role);

        return ApiResult::empty("Rol restaurado correctamente.")->toResponse();
    }
}
