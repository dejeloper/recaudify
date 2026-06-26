<?php

namespace App\OpenApi\LoginAudit;

use OpenApi\Attributes as OA;

class LoginAuditDocs
{
    #[
        OA\Get(
            path: "/api/login-audits",
            summary: "Listar accesos (auditoría de inicios de sesión)",
            description: <<<'MD'
            Feed paginado de inicios de sesión, exitosos y fallidos, con metadata autoritativa del
            servidor (IP, user-agent, SO, dispositivo) y geolocalización del cliente en los exitosos.
            Respuesta estándar paginada `data: { items, meta }`.
            MD,
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Accesos"],
            parameters: [
                new OA\Parameter(
                    name: "status",
                    in: "query",
                    required: false,
                    description: "Filtra por resultado del acceso",
                    schema: new OA\Schema(type: "string", enum: ["success", "failed"]),
                ),
                new OA\Parameter(
                    name: "user_id",
                    in: "query",
                    required: false,
                    description: "Filtra por usuario",
                    schema: new OA\Schema(type: "integer"),
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
                new OA\Response(response: 200, description: "Listado paginado de accesos"),
                new OA\Response(response: 403, description: "Sin permisos"),
            ],
        ),
    ]
    public function index(): void {}
}
