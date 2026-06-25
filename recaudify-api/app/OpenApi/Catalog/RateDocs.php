<?php

namespace App\OpenApi\Catalog;

use OpenApi\Attributes as OA;

class RateDocs
{
    #[
        OA\Get(
            path: "/api/rates",
            summary: "Listar tarifas",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [
                new OA\Response(response: 200, description: "Lista de tarifas (incluye su producto)"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/rates/trashed",
            summary: "Listar tarifas eliminadas",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [new OA\Response(response: 200, description: "Lista de tarifas en papelera")],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/rates/{id}",
            summary: "Obtener tarifa por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Tarifa encontrada"),
                new OA\Response(response: 404, description: "Tarifa no encontrada"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/rates",
            summary: "Crear tarifa",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name", "product_id", "value", "installments", "installment_value"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 100, example: "7 Cuota - Biblia"),
                        new OA\Property(property: "product_id", type: "integer", example: 101),
                        new OA\Property(property: "value", type: "integer", minimum: 0, example: 350000),
                        new OA\Property(property: "installments", type: "integer", minimum: 0, example: 7),
                        new OA\Property(property: "installment_value", type: "integer", minimum: 0, example: 50000),
                        new OA\Property(property: "discount", type: "integer", minimum: 0, example: 0),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Tarifa creada correctamente"),
                new OA\Response(response: 422, description: "Error de validación (p. ej. product_id inexistente)"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/rates/{id}",
            summary: "Actualizar tarifa",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name", "product_id", "value", "installments", "installment_value"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 100, example: "7 Cuota - Biblia"),
                        new OA\Property(property: "product_id", type: "integer", example: 101),
                        new OA\Property(property: "value", type: "integer", minimum: 0, example: 350000),
                        new OA\Property(property: "installments", type: "integer", minimum: 0, example: 7),
                        new OA\Property(property: "installment_value", type: "integer", minimum: 0, example: 50000),
                        new OA\Property(property: "discount", type: "integer", minimum: 0, example: 0),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Tarifa actualizada correctamente"),
                new OA\Response(response: 404, description: "Tarifa no encontrada"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/rates/{id}",
            summary: "Eliminar tarifa",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Tarifa eliminada correctamente"),
                new OA\Response(response: 404, description: "Tarifa no encontrada"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/rates/{id}/restore",
            summary: "Restaurar tarifa eliminada",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Tarifa restaurada correctamente"),
                new OA\Response(response: 404, description: "Tarifa no encontrada en papelera"),
            ],
        ),
    ]
    public function restore(): void {}
}
