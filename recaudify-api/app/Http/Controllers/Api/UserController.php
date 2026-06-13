<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/users',
        summary: 'Listar usuarios activos',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de usuarios activos'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json(User::with('roles')->get());
    }

    #[OA\Get(
        path: '/api/users/disabled',
        summary: 'Listar usuarios desactivados (soft delete)',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de usuarios desactivados'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function indexDisabled(): JsonResponse
    {
        $users = User::onlyTrashed()->with('roles')->get();

        return response()->json($users);
    }

    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Obtener usuario por ID',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Usuario encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles', 'permissions')->findOrFail($id);

        return response()->json($user);
    }

    #[OA\Get(
        path: '/api/users/trashed/{id}',
        summary: 'Obtener usuario eliminado por ID',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Usuario eliminado encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function showTrashed(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->with('roles')->findOrFail($id);

        return response()->json($user);
    }

    #[OA\Get(
        path: '/api/users/search/{name}',
        summary: 'Buscar usuarios por nombre',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'name', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Usuarios encontrados'),
        ]
    )]
    public function search(string $name): JsonResponse
    {
        $users = User::with('roles')
            ->where('name', 'like', "%{$name}%")
            ->orWhere('username', 'like', "%{$name}%")
            ->get();

        return response()->json($users);
    }

    #[OA\Post(
        path: '/api/users',
        summary: 'Crear usuario',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'username', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name',                  type: 'string',  example: 'Juan Pérez'),
                    new OA\Property(property: 'username',              type: 'string',  example: 'jperez'),
                    new OA\Property(property: 'email',                 type: 'string',  nullable: true, example: null),
                    new OA\Property(property: 'password',              type: 'string',  format: 'password', example: 'secret1234'),
                    new OA\Property(property: 'password_confirmation', type: 'string',  format: 'password', example: 'secret1234'),
                    new OA\Property(property: 'role',                  type: 'string',  nullable: true, example: 'cobrador'),
                    new OA\Property(property: 'active',                type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->safe()->except('role');
        $user = User::create($data);

        if ($request->filled('role')) {
            $user->assignRole($request->role);
        }

        return response()->json($user->load('roles'), 201);
    }

    #[OA\Put(
        path: '/api/users/{id}',
        summary: 'Actualizar usuario',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name',     type: 'string',  example: 'Nuevo Nombre'),
                    new OA\Property(property: 'username', type: 'string',  example: 'nuevo_usuario'),
                    new OA\Property(property: 'email',    type: 'string',  nullable: true),
                    new OA\Property(property: 'password', type: 'string',  format: 'password', nullable: true),
                    new OA\Property(property: 'role',     type: 'string',  nullable: true, example: 'cobrador'),
                    new OA\Property(property: 'active',   type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = collect($request->safe()->except('role'))
            ->reject(fn ($value) => is_null($value) && in_array(request()->keys()[0] ?? '', ['password']))
            ->filter(fn ($value, $key) => $key !== 'password' || ! empty($value))
            ->toArray();

        $user->update($data);

        if ($request->has('role')) {
            $user->syncRoles(array_filter([$request->role]));
        }

        return response()->json($user->load('roles'));
    }

    #[OA\Delete(
        path: '/api/users/{id}',
        summary: 'Desactivar usuario (soft delete)',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Usuario desactivado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario desactivado correctamente.']);
    }

    #[OA\Post(
        path: '/api/users/{id}/restore',
        summary: 'Restaurar usuario desactivado',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Usuario restaurado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function restore(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'Usuario restaurado correctamente.']);
    }

    #[OA\Post(
        path: '/api/users/{id}/permissions',
        summary: 'Asignar o revocar permisos directos a un usuario',
        security: [['bearerAuth' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permissions', 'assign'],
                properties: [
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string', example: 'clientes.ver')),
                    new OA\Property(property: 'assign', type: 'boolean', example: true, description: 'true para asignar, false para revocar'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permisos actualizados'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function syncPermissions(int $id): JsonResponse
    {
        request()->validate([
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'assign'        => ['required', 'boolean'],
        ]);

        $user = User::findOrFail($id);

        if (request()->boolean('assign')) {
            $user->givePermissionTo(request()->permissions);
            $message = 'Permisos asignados correctamente.';
        } else {
            $user->revokePermissionTo(request()->permissions);
            $message = 'Permisos revocados correctamente.';
        }

        return response()->json([
            'message'     => $message,
            'permissions' => $user->fresh()->getAllPermissions()->pluck('name'),
        ]);
    }
}
