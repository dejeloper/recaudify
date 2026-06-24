<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        return ApiResult::success(UserResource::collection(User::with("roles")->get()))->toResponse();
    }

    public function indexDisabled(): JsonResponse
    {
        return ApiResult::success(UserResource::collection(User::onlyTrashed()->with("roles")->get()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with("roles", "permissions")->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        return ApiResult::success(new UserResource($user))->toResponse();
    }

    public function showTrashed(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->with("roles")->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        return ApiResult::success(new UserResource($user))->toResponse();
    }

    public function search(string $name): JsonResponse
    {
        $users = User::with("roles")
            ->where("name", "like", "%{$name}%")
            ->orWhere("username", "like", "%{$name}%")
            ->get();

        return ApiResult::success(UserResource::collection($users))->toResponse();
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->except("role"));

        if ($request->filled("role")) {
            $user->syncRoles([$request->role]);
        }

        return ApiResult::created(
            new UserResource($user->load("roles", "permissions")),
            "Usuario creado correctamente.",
        )->toResponse();
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $data = collect($request->safe()->except("role"))
            ->filter(fn($value, $key) => $key !== "password" || !empty($value))
            ->toArray();

        $user->update($data);

        if ($request->has("role")) {
            $user->syncRoles(array_filter([$request->role]));
        }

        return ApiResult::success(
            new UserResource($user->load("roles", "permissions")),
            "Usuario actualizado correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $user->delete();

        return ApiResult::empty("Usuario desactivado correctamente.")->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        $user->restore();

        return ApiResult::empty("Usuario restaurado correctamente.")->toResponse();
    }

    public function syncPermissions(int $id): JsonResponse
    {
        request()->validate([
            "permissions" => ["required", "array", "min:1"],
            "permissions.*" => ["string", "exists:permissions,name"],
            "assign" => ["required", "boolean"],
        ]);

        $user = User::find($id);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        if (request()->boolean("assign")) {
            $user->givePermissionTo(request()->permissions);
            $message = "Permisos asignados correctamente.";
        } else {
            $user->revokePermissionTo(request()->permissions);
            $message = "Permisos revocados correctamente.";
        }

        return ApiResult::success($user->fresh()->getAllPermissions()->pluck("name"), $message)->toResponse();
    }
}
