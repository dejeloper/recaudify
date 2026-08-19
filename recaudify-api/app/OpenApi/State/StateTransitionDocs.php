<?php

namespace App\OpenApi\State;

use OpenApi\Attributes as OA;

class StateTransitionDocs
{
    #[
        OA\Get(
            path: "/api/state-transitions",
            summary: "Listar transiciones permitidas",
            description: <<<'MD'
            El grafo del ciclo de vida: lo que no está declarado acá, el motor no lo deja ocurrir.
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
                new OA\Response(response: 200, description: "Listado de transiciones"),
                new OA\Response(response: 403, description: "Sin permisos (requiere states.view)"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/state-transitions/trashed",
            summary: "Listar transiciones eliminadas",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            responses: [new OA\Response(response: 200, description: "Listado de transiciones eliminadas")],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Get(
            path: "/api/state-transitions/{id}",
            summary: "Consultar una transición",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Transición"),
                new OA\Response(response: 404, description: "No encontrada"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Post(
            path: "/api/state-transitions",
            summary: "Crear una transición",
            description: <<<'MD'
            `from_state_id` en null declara la **transición de creación**: el estado con el que nace el
            registro. Se rechaza si ambos estados no son de la misma entidad, si la transición ya
            existe, si va a su mismo estado, o si sale de un estado final.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["entity", "to_state_id"],
                    properties: [
                        new OA\Property(property: "entity", type: "string", example: "contract"),
                        new OA\Property(
                            property: "from_state_id",
                            type: "integer",
                            nullable: true,
                            description: "Null = transición de creación",
                        ),
                        new OA\Property(property: "to_state_id", type: "integer"),
                        new OA\Property(
                            property: "permission",
                            type: "string",
                            nullable: true,
                            description: "Permiso exigido para ejecutarla a mano",
                        ),
                        new OA\Property(
                            property: "is_automatic",
                            type: "boolean",
                            default: false,
                            description: "La ejecuta el motor; una persona no puede",
                        ),
                        new OA\Property(property: "requires_authorization", type: "boolean", default: false),
                        new OA\Property(property: "requires_reason", type: "boolean", default: false),
                        new OA\Property(property: "label", type: "string", nullable: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Transición creada"),
                new OA\Response(response: 422, description: "Transición inválida o duplicada"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/state-transitions/{id}",
            summary: "Actualizar las condiciones de una transición",
            description: "Los estados de origen y destino no se cambian: se elimina y se crea otra.",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Transición actualizada"),
                new OA\Response(response: 404, description: "No encontrada"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/state-transitions/{id}",
            summary: "Eliminar una transición",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Transición eliminada"),
                new OA\Response(response: 404, description: "No encontrada"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/state-transitions/{id}/restore",
            summary: "Restaurar una transición eliminada",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Estados"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Transición restaurada"),
                new OA\Response(response: 404, description: "No encontrada"),
            ],
        ),
    ]
    public function restore(): void {}
}
