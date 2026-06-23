<?php

namespace App\OpenApi\Role;

use OpenApi\Attributes as OA;

class RoleDocs
{
    #[OA\Get(
        path: '/api/roles',
        summary: 'Listar roles con sus permisos',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Roles'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de roles con sus permisos'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Obtener rol por ID',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Rol encontrado'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
        ]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/api/roles',
        summary: 'Crear rol',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
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
            new OA\Response(response: 201, description: 'Rol creado correctamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(): void {}

    #[OA\Put(
        path: '/api/roles/{id}',
        summary: 'Actualizar rol',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
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
            new OA\Response(response: 200, description: 'Rol actualizado correctamente'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Eliminar rol',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Rol eliminado correctamente'),
            new OA\Response(response: 404, description: 'Rol no encontrado'),
        ]
    )]
    public function destroy(): void {}

    #[OA\Get(
        path: '/api/roles/trashed',
        summary: 'Listar roles eliminados',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Roles'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de roles en papelera'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ]
    )]
    public function trashed(): void {}

    #[OA\Post(
        path: '/api/roles/{id}/restore',
        summary: 'Restaurar rol eliminado',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Roles'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Rol restaurado correctamente'),
            new OA\Response(response: 404, description: 'Rol no encontrado en papelera'),
        ]
    )]
    public function restore(): void {}
}
