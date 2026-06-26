<?php

namespace App\OpenApi\Catalog;

use OpenApi\Attributes as OA;

class CallReasonDocs
{
    #[
        OA\Get(
            path: "/api/call-reasons",
            summary: "Listar motivos de llamada",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [
                new OA\Response(response: 200, description: "Lista de motivos de llamada"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/call-reasons/trashed",
            summary: "Listar motivos de llamada eliminados",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            responses: [new OA\Response(response: 200, description: "Lista de motivos en papelera")],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/call-reasons/{id}",
            summary: "Obtener motivo de llamada por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Motivo encontrado"),
                new OA\Response(response: 404, description: "Motivo no encontrado"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/call-reasons",
            summary: "Crear motivo de llamada",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name"],
                    properties: [
                        new OA\Property(property: "name", type: "string", maxLength: 30, example: "Programar Pago"),
                        new OA\Property(
                            property: "color",
                            type: "string",
                            maxLength: 30,
                            nullable: true,
                            example: "green",
                        ),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Motivo creado correctamente"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/call-reasons/{id}",
            summary: "Actualizar motivo de llamada",
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
                        new OA\Property(property: "name", type: "string", maxLength: 30, example: "Programar Pago"),
                        new OA\Property(
                            property: "color",
                            type: "string",
                            maxLength: 30,
                            nullable: true,
                            example: "green",
                        ),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Motivo actualizado correctamente"),
                new OA\Response(response: 404, description: "Motivo no encontrado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/call-reasons/{id}",
            summary: "Eliminar motivo de llamada",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Motivo eliminado correctamente"),
                new OA\Response(response: 404, description: "Motivo no encontrado"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/call-reasons/{id}/restore",
            summary: "Restaurar motivo de llamada eliminado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Motivo restaurado correctamente"),
                new OA\Response(response: 404, description: "Motivo no encontrado en papelera"),
            ],
        ),
    ]
    public function restore(): void {}
}
