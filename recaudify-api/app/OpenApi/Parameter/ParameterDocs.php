<?php

namespace App\OpenApi\Parameter;

use OpenApi\Attributes as OA;

class ParameterDocs
{
    #[OA\Get(
        path: '/api/parameters',
        summary: 'Listar parámetros',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de parámetros del sistema'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ]
    )]
    public function index(): void {}

    #[OA\Get(
        path: '/api/parameters/trashed',
        summary: 'Listar parámetros eliminados',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de parámetros en papelera'),
            new OA\Response(response: 403, description: 'Sin permisos'),
        ]
    )]
    public function trashed(): void {}

    #[OA\Get(
        path: '/api/parameters/{id}',
        summary: 'Obtener parámetro por ID',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Parámetro encontrado'),
            new OA\Response(response: 404, description: 'Parámetro no encontrado'),
        ]
    )]
    public function show(): void {}

    #[OA\Post(
        path: '/api/parameters',
        summary: 'Crear parámetro',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['key', 'value'],
                properties: [
                    new OA\Property(property: 'key', type: 'string', maxLength: 100, example: 'max_intentos_login'),
                    new OA\Property(property: 'value', type: 'string', maxLength: 255, example: '5'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Número máximo de intentos de login fallidos'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Parámetro creado correctamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(): void {}

    #[OA\Put(
        path: '/api/parameters/{id}',
        summary: 'Actualizar parámetro',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['key', 'value'],
                properties: [
                    new OA\Property(property: 'key', type: 'string', maxLength: 100, example: 'max_intentos_login'),
                    new OA\Property(property: 'value', type: 'string', maxLength: 255, example: '10'),
                    new OA\Property(property: 'description', type: 'string', maxLength: 255, nullable: true, example: 'Número máximo de intentos de login fallidos'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Parámetro actualizado correctamente'),
            new OA\Response(response: 404, description: 'Parámetro no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(): void {}

    #[OA\Delete(
        path: '/api/parameters/{id}',
        summary: 'Eliminar parámetro',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Parámetro eliminado correctamente'),
            new OA\Response(response: 404, description: 'Parámetro no encontrado'),
        ]
    )]
    public function destroy(): void {}

    #[OA\Post(
        path: '/api/parameters/{id}/restore',
        summary: 'Restaurar parámetro eliminado',
        security: [['bearerAuth' => []], ['cookieAuth' => []]],
        tags: ['Parámetros'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Parámetro restaurado correctamente'),
            new OA\Response(response: 404, description: 'Parámetro no encontrado en papelera'),
        ]
    )]
    public function restore(): void {}
}
