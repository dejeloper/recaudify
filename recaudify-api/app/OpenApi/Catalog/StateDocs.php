<?php

namespace App\OpenApi\Catalog;

use OpenApi\Attributes as OA;

class StateDocs
{
    #[
        OA\Get(
            path: "/api/states",
            summary: "Listar estados",
            description: "Catálogo de estados (códigos legacy 101–127). Filtrable por tipo de entidad.",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(
                    name: "type",
                    in: "query",
                    required: false,
                    description: "Agrupador del estado",
                    schema: new OA\Schema(
                        type: "string",
                        enum: ["user", "client", "seller", "contract", "scheduled_payment", "collector"],
                    ),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Lista de estados"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/states/{id}",
            summary: "Obtener estado por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Catálogos"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Estado encontrado"),
                new OA\Response(response: 404, description: "Estado no encontrado"),
            ],
        ),
    ]
    public function show(): void {}
}
