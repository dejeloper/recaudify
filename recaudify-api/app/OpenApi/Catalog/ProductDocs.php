<?php

namespace App\OpenApi\Catalog;

use OpenApi\Attributes as OA;

class ProductDocs
{
    #[
        OA\Get(
            path: "/api/products",
            summary: "Listar productos",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [
                new OA\Response(response: 200, description: "Lista de productos"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/products/trashed",
            summary: "Listar productos eliminados",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [
                new OA\Response(response: 200, description: "Lista de productos en papelera"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/products/{id}",
            summary: "Obtener producto por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Producto encontrado"),
                new OA\Response(response: 404, description: "Producto no encontrado"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/products",
            summary: "Crear producto",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name", "value"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 100, example: "Biblia Grande"),
                        new OA\Property(property: "value", type: "integer", minimum: 0, example: 350000),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Producto creado correctamente"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/products/{id}",
            summary: "Actualizar producto",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name", "value"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 100, example: "Biblia Grande"),
                        new OA\Property(property: "value", type: "integer", minimum: 0, example: 300000),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Producto actualizado correctamente"),
                new OA\Response(response: 404, description: "Producto no encontrado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/products/{id}",
            summary: "Eliminar producto",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Producto eliminado correctamente"),
                new OA\Response(response: 404, description: "Producto no encontrado"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/products/{id}/restore",
            summary: "Restaurar producto eliminado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Producto restaurado correctamente"),
                new OA\Response(response: 404, description: "Producto no encontrado en papelera"),
            ],
        ),
    ]
    public function restore(): void {}
}
