<?php

namespace App\OpenApi\Permission;

use OpenApi\Attributes as OA;

class PermissionDocs
{
    #[OA\Get(
        path: '/api/permissions',
        summary: 'Listar permisos',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Permisos'],
        responses: [new OA\Response(response: 200, description: 'Lista de permisos')]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/permissions/{id}',
        summary: 'Obtener permiso por ID',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Permiso encontrado'),
            new OA\Response(response: 404, description: 'Permiso no encontrado'),
        ]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/api/permissions',
        summary: 'Crear permiso',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Permisos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [new OA\Property(property: 'name', type: 'string', pattern: '^[a-z_]+\.[a-z_-]+$', description: 'Formato modulo.accion (minúsculas, guion bajo y guion).', example: 'clientes.exportar')]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Permiso creado correctamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(): void {}

    #[OA\Put(
        path: '/api/permissions/{id}',
        summary: 'Actualizar permiso',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['name'],
                properties: [new OA\Property(property: 'name', type: 'string', pattern: '^[a-z_]+\.[a-z_-]+$', description: 'Formato modulo.accion (minúsculas, guion bajo y guion).', example: 'clientes.exportar')]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permiso actualizado correctamente'),
            new OA\Response(response: 404, description: 'Permiso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/api/permissions/{id}',
        summary: 'Eliminar permiso',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Permiso eliminado correctamente'),
            new OA\Response(response: 404, description: 'Permiso no encontrado'),
        ]
    )]
    public function destroy(): void {}
}
