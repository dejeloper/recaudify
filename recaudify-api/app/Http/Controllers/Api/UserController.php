<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\SyncPermissionsRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResult;
use App\Services\PasswordResetService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(UserResource::collection($this->userService->all()))->toResponse();
    }

    public function indexDisabled(): JsonResponse
    {
        return ApiResult::success(UserResource::collection($this->userService->allDisabled()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        return ApiResult::success(new UserResource($user))->toResponse();
    }

    public function showTrashed(int $id): JsonResponse
    {
        $user = $this->userService->findTrashed($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        return ApiResult::success(new UserResource($user))->toResponse();
    }

    public function search(string $name): JsonResponse
    {
        return ApiResult::success(UserResource::collection($this->userService->search($name)))->toResponse();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->safe()->except("role"), $request->string("role")->toString());

        return ApiResult::created(new UserResource($user), "Usuario creado correctamente.")->toResponse();
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $updated = $this->userService->update(
            $user,
            $request->safe()->except("role"),
            $request->has("role"),
            $request->string("role")->toString(),
        );

        return ApiResult::success(new UserResource($updated), "Usuario actualizado correctamente.")->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $this->userService->delete($user);

        return ApiResult::empty("Usuario desactivado correctamente.")->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->findTrashed($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $this->userService->restore($user);

        return ApiResult::empty("Usuario restaurado correctamente.")->toResponse();
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $password = $this->passwordResetService->reset($user, $request->user()?->id);

        return ApiResult::success(["password" => $password], "Contraseña reseteada correctamente.")->toResponse();
    }

    public function syncPermissions(SyncPermissionsRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $assign = $request->boolean("assign");
        $permissions = $this->userService->syncPermissions($user, $request->permissions, $assign);
        $message = $assign ? "Permisos asignados correctamente." : "Permisos revocados correctamente.";

        return ApiResult::success($permissions, $message)->toResponse();
    }
}
