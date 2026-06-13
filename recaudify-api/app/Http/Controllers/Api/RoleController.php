<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    #[OA\Get(
        path: '/api/roles',
        summary: 'Listar roles con sus permisos',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        responses: [new OA\Response(response: 200, description: 'Lista de roles')]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Role::where('guard_name', 'api')->with('permissions')->get());
    }

    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Obtener rol por ID',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Rol encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->with('permissions')->findOrFail($id);

        return response()->json($role);
    }

    #[OA\Post(
        path: '/api/roles',
        summary: 'Crear rol',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'gestor'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string', example: 'clientes.ver')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rol creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(): JsonResponse
    {
        request()->validate([
            'name'          => ['required', 'string', 'unique:roles,name'],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => request()->name, 'guard_name' => 'api']);

        if (request()->filled('permissions')) {
            $role->syncPermissions(request()->permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    #[OA\Put(
        path: '/api/roles/{id}',
        summary: 'Actualizar rol',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'gestor'),
                    new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string', example: 'clientes.ver')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rol actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function update(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->findOrFail($id);

        request()->validate([
            'name'          => ['sometimes', 'string', 'unique:roles,name,' . $id],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        if (request()->filled('name')) {
            $role->update(['name' => request()->name]);
        }

        if (request()->has('permissions')) {
            $role->syncPermissions(request()->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Eliminar rol',
        security: [['bearerAuth' => []]],
        tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Rol eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $role = Role::where('guard_name', 'api')->findOrFail($id);
        $role->syncPermissions([]);
        $role->delete();

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }
}
