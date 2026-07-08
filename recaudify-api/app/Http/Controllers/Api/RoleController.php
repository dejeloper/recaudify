<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use App\Services\ParameterService;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends ApiController
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly LoggingService $logging,
        private readonly ParameterService $parameterService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (!$request->filled("page")) {
            return ApiResult::success(RoleResource::collection($this->roleService->all()))->toResponse();
        }

        $search = $request->filled("search") ? $request->string("search")->toString() : null;

        $defaultPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_per_page");
        $maxPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_max_per_page");

        $perPage = (int) $request->query("per_page", (string) $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $paginator = $this->roleService->paginate($search, $perPage);

        return ApiResult::paginated($paginator, RoleResource::collection($paginator->getCollection()))->toResponse();
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

        $this->logging->logBusiness("Rol creado", [
            "role_id" => $role->id,
            "name" => $role->name,
            "permissions" => $request->filled("permissions") ? $request->permissions : [],
            "by_user_id" => $request->user()?->id,
        ]);

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

        $this->logging->logBusiness("Rol actualizado", [
            "role_id" => $updated->id,
            "name" => $updated->name,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(new RoleResource($updated), "Rol actualizado correctamente.")->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleService->find($id);

        if (!$role) {
            return ApiResult::notFound("Rol no encontrado.")->toResponse();
        }

        $this->roleService->delete($role);

        $this->logging->logBusiness("Rol eliminado", [
            "role_id" => $role->id,
            "name" => $role->name,
            "by_user_id" => request()->user()?->id,
        ]);

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

        $this->logging->logBusiness("Rol restaurado", [
            "role_id" => $role->id,
            "name" => $role->name,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Rol restaurado correctamente.")->toResponse();
    }
}
