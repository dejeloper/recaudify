<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;

class PermissionController extends ApiController
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly LoggingService $logging,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(PermissionResource::collection($this->permissionService->all()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $permission = $this->permissionService->find($id);

        if (!$permission) {
            return ApiResult::notFound("Permiso no encontrado.")->toResponse();
        }

        return ApiResult::success(new PermissionResource($permission))->toResponse();
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->create($request->name);

        $this->logging->logBusiness("Permiso creado", [
            "permission_id" => $permission->id,
            "name" => $permission->name,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::created(new PermissionResource($permission), "Permiso creado correctamente.")->toResponse();
    }

    public function update(UpdatePermissionRequest $request, int $id): JsonResponse
    {
        $permission = $this->permissionService->find($id);

        if (!$permission) {
            return ApiResult::notFound("Permiso no encontrado.")->toResponse();
        }

        $this->permissionService->update($permission, $request->name);

        $this->logging->logBusiness("Permiso actualizado", [
            "permission_id" => $permission->id,
            "name" => $request->name,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(
            new PermissionResource($permission),
            "Permiso actualizado correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $permission = $this->permissionService->find($id);

        if (!$permission) {
            return ApiResult::notFound("Permiso no encontrado.")->toResponse();
        }

        $this->permissionService->delete($permission);

        $this->logging->logBusiness("Permiso eliminado", [
            "permission_id" => $permission->id,
            "name" => $permission->name,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Permiso eliminado correctamente.")->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(PermissionResource::collection($this->permissionService->trashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $permission = $this->permissionService->findTrashed($id);

        if (!$permission) {
            return ApiResult::notFound("Permiso no encontrado.")->toResponse();
        }

        $this->permissionService->restore($permission);

        $this->logging->logBusiness("Permiso restaurado", [
            "permission_id" => $permission->id,
            "name" => $permission->name,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Permiso restaurado correctamente.")->toResponse();
    }
}
