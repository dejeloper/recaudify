<?php

namespace App\OpenApi\Health;

use OpenApi\Attributes as OA;

class HealthDocs
{
    #[
        OA\Get(
            path: "/api/health",
            summary: "Estado de la aplicación y sus dependencias",
            description: <<<'MD'
            Endpoint **público**, pensado para chequeos de uptime externos: exigir token lo volvería
            inútil justo cuando la base está caída.

            Comprueba base de datos, caché, almacenamiento y cola. Devuelve **200** si todo responde
            (`ok`) o si hay señales de alerta sin caída (`degraded`, ej. jobs fallidos acumulados), y
            **503** si falla alguna dependencia crítica (base de datos, caché o almacenamiento).

            No expone mensajes de error ni versiones: el detalle del fallo va al canal `app-errors`.
            MD,
            tags: ["Sistema"],
            responses: [
                new OA\Response(
                    response: 200,
                    description: "Servicio operativo (ok o degraded)",
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: "success", type: "boolean", example: true),
                            new OA\Property(
                                property: "data",
                                properties: [
                                    new OA\Property(
                                        property: "status",
                                        type: "string",
                                        enum: ["ok", "degraded", "down"],
                                    ),
                                    new OA\Property(property: "timestamp", type: "string", format: "date-time"),
                                    new OA\Property(
                                        property: "checks",
                                        properties: [
                                            new OA\Property(
                                                property: "database",
                                                properties: [
                                                    new OA\Property(property: "status", type: "string"),
                                                    new OA\Property(property: "duration_ms", type: "integer"),
                                                ],
                                                type: "object",
                                            ),
                                            new OA\Property(property: "cache", type: "object"),
                                            new OA\Property(property: "storage", type: "object"),
                                            new OA\Property(
                                                property: "queue",
                                                properties: [
                                                    new OA\Property(property: "status", type: "string"),
                                                    new OA\Property(property: "driver", type: "string"),
                                                    new OA\Property(property: "pending", type: "integer"),
                                                    new OA\Property(property: "failed", type: "integer"),
                                                ],
                                                type: "object",
                                            ),
                                        ],
                                        type: "object",
                                    ),
                                ],
                                type: "object",
                            ),
                        ],
                    ),
                ),
                new OA\Response(response: 503, description: "Alguna dependencia crítica está caída"),
            ],
        ),
    ]
    public function index(): void {}
}
