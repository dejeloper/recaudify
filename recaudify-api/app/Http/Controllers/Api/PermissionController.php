<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    #[OA\Get(
        path: '/api/permissions',
        summary: 'Listar permisos',
        security: [['bearerAuth' => []]],
        tags: ['Permisos'],
        responses: [new OA\Response(response: 200, description: 'Lista de permisos')]
    )]
    public function index(): JsonResponse
    {
        return response()->json(Permission::where('guard_name', 'api')->get());
    }

    #[OA\Get(
        path: '/api/permissions/{id}',
        summary: 'Obtener permiso por ID',
        security: [['bearerAuth' => []]],
        tags: ['Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Permiso encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        return response()->json(Permission::where('guard_name', 'api')->findOrFail($id));
    }

    #[OA\Post(
        path: '/api/permissions',
        summary: 'Crear permiso',
        security: [['bearerAuth' => []]],
        tags: ['Permisos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [new OA\Property(property: 'name', type: 'string', example: 'clientes.exportar')]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Permiso creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(): JsonResponse
    {
        request()->validate([
            'name' => ['required', 'string', 'unique:permissions,name'],
        ]);

        $permission = Permission::create(['name' => request()->name, 'guard_name' => 'api']);

        return response()->json($permission, 201);
    }

    #[OA\Put(
        path: '/api/permissions/{id}',
        summary: 'Actualizar permiso',
        security: [['bearerAuth' => []]],
        tags: ['Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [new OA\Property(property: 'name', type: 'string', example: 'clientes.exportar')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permiso actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function update(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->findOrFail($id);

        request()->validate([
            'name' => ['required', 'string', 'unique:permissions,name,' . $id],
        ]);

        $permission->update(['name' => request()->name]);

        return response()->json($permission);
    }

    #[OA\Delete(
        path: '/api/permissions/{id}',
        summary: 'Eliminar permiso',
        security: [['bearerAuth' => []]],
        tags: ['Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Permiso eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $permission = Permission::where('guard_name', 'api')->findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permiso eliminado correctamente.']);
    }
}
