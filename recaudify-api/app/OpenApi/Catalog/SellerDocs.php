<?php

namespace App\OpenApi\Catalog;

use OpenApi\Attributes as OA;

class SellerDocs
{
    #[
        OA\Get(
            path: "/api/sellers",
            summary: "Listar vendedores",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [
                new OA\Response(response: 200, description: "Lista de vendedores"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/sellers/trashed",
            summary: "Listar vendedores eliminados",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [new OA\Response(response: 200, description: "Lista de vendedores en papelera")],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/sellers/{id}",
            summary: "Obtener vendedor por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Vendedor encontrado"),
                new OA\Response(response: 404, description: "Vendedor no encontrado"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/sellers",
            summary: "Crear vendedor",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 100, example: "Fabiola Guzmán"),
                        new OA\Property(
                            property: "username",
                            type: "string",
                            maxLength: 30,
                            nullable: true,
                            example: "Vendedor1",
                        ),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Vendedor creado correctamente"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/sellers/{id}",
            summary: "Actualizar vendedor",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 100, example: "Fabiola Guzmán"),
                        new OA\Property(
                            property: "username",
                            type: "string",
                            maxLength: 30,
                            nullable: true,
                            example: "Vendedor1",
                        ),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Vendedor actualizado correctamente"),
                new OA\Response(response: 404, description: "Vendedor no encontrado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/sellers/{id}",
            summary: "Eliminar vendedor",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Vendedor eliminado correctamente"),
                new OA\Response(response: 404, description: "Vendedor no encontrado"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/sellers/{id}/restore",
            summary: "Restaurar vendedor eliminado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Vendedor restaurado correctamente"),
                new OA\Response(response: 404, description: "Vendedor no encontrado en papelera"),
            ],
        ),
    ]
    public function restore(): void {}
}
