<?php

namespace App\OpenApi\State;

use OpenApi\Attributes as OA;

class StateDocs
{
    #[
        OA\Get(
            path: "/api/states",
            summary: "Listar estados del ciclo de vida",
            description: <<<'MD'
            Catálogo de estados por entidad de negocio (client, contract, payment, commitment).
            Agregar un estado es un registro más acá, no un cambio de código.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(
                    name: "entity",
                    in: "query",
                    required: false,
                    description: "Filtra por entidad",
                    schema: new OA\Schema(type: "string", example: "contract"),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Listado de estados"),
                new OA\Response(response: 403, description: "Sin permisos (requiere states.view)"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/states/entities",
            summary: "Entidades con ciclo de vida configurado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            responses: [new OA\Response(response: 200, description: "Lista de claves de entidad")],
        ),
    ]
    public function entities(): void {}

    #[
        OA\Get(
            path: "/api/states/trashed",
            summary: "Listar estados eliminados",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            responses: [new OA\Response(response: 200, description: "Listado de estados eliminados")],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/states/{id}",
            summary: "Consultar un estado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Estado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/states",
            summary: "Crear un estado",
            description: <<<'MD'
            Marcar el estado como inicial desmarca automáticamente al anterior: cada entidad tiene un
            solo estado inicial, que es con el que nacen sus registros.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["entity", "key", "name"],
                    properties: [
                        new OA\Property(property: "entity", type: "string", example: "contract"),
                        new OA\Property(
                            property: "key",
                            type: "string",
                            example: "pending_validation",
                            description: "Minúsculas, números y guion bajo, empezando por letra",
                        ),
                        new OA\Property(property: "name", type: "string", example: "Pendiente de validación"),
                        new OA\Property(property: "description", type: "string", nullable: true),
                        new OA\Property(property: "color", type: "string", example: "#eab308"),
                        new OA\Property(property: "icon", type: "string", nullable: true),
                        new OA\Property(property: "is_initial", type: "boolean", default: false),
                        new OA\Property(property: "is_final", type: "boolean", default: false),
                        new OA\Property(property: "sort_order", type: "integer", default: 0),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Estado creado"),
                new OA\Response(response: 422, description: "Clave duplicada o formato inválido"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/states/{id}",
            summary: "Actualizar un estado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Estado actualizado"),
                new OA\Response(
                    response: 422,
                    description: "No se puede marcar como final un estado con transiciones de salida",
                ),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/states/{id}",
            summary: "Eliminar un estado",
            description: <<<'MD'
            Borrado lógico. Se rechaza si el estado es el inicial de su entidad o si alguna transición
            lo usa: primero hay que reconfigurar el ciclo de vida.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Estado eliminado"),
                new OA\Response(response: 422, description: "Es el estado inicial o está en uso"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/states/{id}/restore",
            summary: "Restaurar un estado eliminado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Estado restaurado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function restore(): void {}
}
