<?php

namespace App\OpenApi\Activity;

use OpenApi\Attributes as OA;

class ActivityDocs
{
    #[
        OA\Get(
            path: "/api/activities",
            summary: "Listar actividad (auditoría de cambios)",
            description: <<<'MD'
            Feed paginado de cambios de negocio (creó / actualizó / eliminó / restauró) con su autor,
            la entidad afectada y el diff de campos. Respuesta estándar paginada `data: { items, meta }`.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auditoría"],
            parameters: [
                new OA\Parameter(
                    name: "model",
                    in: "query",
                    required: false,
                    description: "Filtra por modelo (basename, ej. Product, Rate, Seller, CallReason)",
                    schema: new OA\Schema(type: "string", example: "Product"),
                ),
                new OA\Parameter(
                    name: "subject_id",
                    in: "query",
                    required: false,
                    description: "Filtra por ID del registro afectado",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "causer_id",
                    in: "query",
                    required: false,
                    description: "Filtra por usuario que realizó la acción",
                    schema: new OA\Schema(type: "integer"),
                ),
                new OA\Parameter(
                    name: "log_name",
                    in: "query",
                    required: false,
                    description: "Filtra por nombre del log (ej. catalogos)",
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "per_page",
                    in: "query",
                    required: false,
                    description: "Elementos por página (1–100, por defecto 25)",
                    schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 25),
                ),
                new OA\Parameter(
                    name: "page",
                    in: "query",
                    required: false,
                    schema: new OA\Schema(type: "integer", minimum: 1, default: 1),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Listado paginado de actividad"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}
}
