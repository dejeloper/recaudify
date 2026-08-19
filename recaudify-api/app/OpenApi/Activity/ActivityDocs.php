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
                    name: "user",
                    in: "query",
                    required: false,
                    description: 'Filtra por username del autor. El valor "sistema" trae la actividad sin autor',
                    schema: new OA\Schema(type: "string"),
                ),
                new OA\Parameter(
                    name: "from",
                    in: "query",
                    required: false,
                    description: "Fecha inicial del rango (inclusive)",
                    schema: new OA\Schema(type: "string", format: "date-time", example: "2026-01-01"),
                ),
                new OA\Parameter(
                    name: "to",
                    in: "query",
                    required: false,
                    description: "Fecha final del rango (inclusive). No puede ser anterior a from",
                    schema: new OA\Schema(type: "string", format: "date-time", example: "2026-01-31 23:59:59"),
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

    #[
        OA\Get(
            path: "/api/activities/purge/preview",
            summary: "Vista previa de la purga del log",
            description: <<<'MD'
            Cuántos registros eliminaría la purga con el periodo de retención vigente, sin borrar nada.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auditoría"],
            parameters: [
                new OA\Parameter(
                    name: "days",
                    in: "query",
                    required: false,
                    description: "Días de retención a simular. Por defecto usa el parámetro activity_log_retention_days",
                    schema: new OA\Schema(type: "integer", minimum: 1, example: 365),
                ),
            ],
            responses: [
                new OA\Response(response: 200, description: "Conteo, fecha de corte y días de retención"),
                new OA\Response(response: 403, description: "Sin permisos (requiere audit.purge)"),
            ],
        ),
    ]
    public function purgePreview(): void {}

    #[
        OA\Post(
            path: "/api/activities/purge",
            summary: "Purgar el log de actividad vencido",
            description: <<<'MD'
            Única vía de borrado del log: no existe borrado individual ni edición. Elimina la actividad
            anterior al periodo de retención y **deja registro de la propia purga** (quién la ejecutó,
            cuántos registros eliminó y con qué fecha de corte).
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auditoría"],
            requestBody: new OA\RequestBody(
                required: false,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "days",
                            type: "integer",
                            minimum: 1,
                            example: 365,
                            description: "Días de retención. Por defecto usa el parámetro activity_log_retention_days",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Registros eliminados, fecha de corte y retención aplicada",
                ),
                new OA\Response(response: 403, description: "Sin permisos (requiere audit.purge)"),
                new OA\Response(response: 422, description: "days inválido"),
            ],
        ),
    ]
    public function purge(): void {}
}
