<?php

namespace App\OpenApi\Branch;

use OpenApi\Attributes as OA;

class BranchDocs
{
    #[
        OA\Get(
            path: "/api/branches",
            summary: "Listar sucursales",
            description: <<<'MD'
            Catálogo de sedes físicas de la empresa. No es multi-tenancy: todas las sucursales
            comparten la misma base y la misma cartera. Lo que cambia por sucursal es el alcance
            operativo de usuarios, cobradores y medios de pago.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            parameters: [
                new OA\Parameter(
                    name: "search",
                    in: "query",
                    required: false,
                    description: "Filtra por código, nombre o ciudad",
                    schema: new OA\Schema(type: "string", example: "PRINCIPAL"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Listado de sucursales"),
                new OA\Response(response: 403, description: "Sin permisos (requiere branches.view)"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/branches/main",
            summary: "Sucursal principal",
            description: "Sucursal usada por defecto cuando un registro no trae sucursal asignada.",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            responses: [
                new OA\Response(response: 200, description: "Sucursal principal"),
                new OA\Response(response: 404, description: "No hay sucursal principal configurada"),
            ],
        ),
    ]
    public function main(): void {}

    #[
        OA\Get(
            path: "/api/branches/trashed",
            summary: "Listar sucursales eliminadas",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            responses: [new OA\Response(response: 200, description: "Listado de sucursales eliminadas")],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/branches/{id}",
            summary: "Detalle de una sucursal",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Sucursal"),
                new OA\Response(response: 404, description: "Sucursal no encontrada"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/branches",
            summary: "Crear sucursal",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["code", "name"],
                    properties: [
                        new OA\Property(property: "code", type: "string", example: "BOG-CEN"),
                        new OA\Property(property: "name", type: "string", example: "Bogotá - Centro"),
                        new OA\Property(property: "address", type: "string", nullable: true),
                        new OA\Property(property: "city", type: "string", nullable: true, example: "Bogotá"),
                        new OA\Property(property: "phone", type: "string", nullable: true),
                        new OA\Property(property: "email", type: "string", nullable: true),
                        new OA\Property(property: "is_main", type: "boolean", example: false),
                        new OA\Property(property: "sort_order", type: "integer", example: 1),
                    ],
                ),
            ),
            tags: ["Sucursales"],
            responses: [
                new OA\Response(response: 201, description: "Sucursal creada"),
                new OA\Response(response: 422, description: "Código o nombre duplicado"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/branches/{id}",
            summary: "Actualizar sucursal",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Sucursal actualizada"),
                new OA\Response(response: 404, description: "Sucursal no encontrada"),
                new OA\Response(response: 422, description: "Datos inválidos o quedaría sin sucursal principal"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/branches/{id}",
            summary: "Eliminar sucursal",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Sucursal eliminada"),
                new OA\Response(response: 404, description: "Sucursal no encontrada"),
                new OA\Response(response: 422, description: "Es la principal o tiene usuarios asignados"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/branches/{id}/restore",
            summary: "Restaurar sucursal eliminada",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Sucursales"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Sucursal restaurada"),
                new OA\Response(response: 404, description: "Sucursal no encontrada"),
            ],
        ),
    ]
    public function restore(): void {}
}
