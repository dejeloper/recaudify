<?php

namespace App\OpenApi\UserSchedule;

use OpenApi\Attributes as OA;

class UserScheduleDocs
{
    #[
        OA\Get(
            path: "/api/users/{userId}/schedules",
            summary: "Listar horarios de un usuario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Horarios"],
            parameters: [
                new OA\Parameter(name: "userId", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Horarios activos del usuario"),
                new OA\Response(response: 404, description: "Usuario no encontrado"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/users/{userId}/schedules/trashed",
            summary: "Listar horarios eliminados de un usuario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Horarios"],
            parameters: [
                new OA\Parameter(name: "userId", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Horarios en papelera del usuario"),
                new OA\Response(response: 404, description: "Usuario no encontrado"),
            ],
        ),
    ]
    public function trashed(): void {}

    #[
        OA\Post(
            path: "/api/users/{userId}/schedules",
            summary: "Crear horario para un usuario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Horarios"],
            parameters: [
                new OA\Parameter(name: "userId", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["day_of_week", "start_time", "end_time"],
                    properties: [
                        new OA\Property(
                            property: "day_of_week",
                            type: "integer",
                            minimum: 0,
                            maximum: 6,
                            description: "0 = domingo, 1 = lunes, …, 6 = sábado",
                            example: 1,
                        ),
                        new OA\Property(property: "start_time", type: "string", format: "time", example: "08:00"),
                        new OA\Property(property: "end_time", type: "string", format: "time", example: "17:00"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Horario creado correctamente"),
                new OA\Response(response: 404, description: "Usuario no encontrado"),
                new OA\Response(response: 409, description: "Ya existe un horario para ese día"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/schedules/{id}",
            summary: "Actualizar horario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Horarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "day_of_week", type: "integer", minimum: 0, maximum: 6, example: 1),
                        new OA\Property(property: "start_time", type: "string", format: "time", example: "09:00"),
                        new OA\Property(property: "end_time", type: "string", format: "time", example: "18:00"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Horario actualizado correctamente"),
                new OA\Response(response: 404, description: "Horario no encontrado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/schedules/{id}",
            summary: "Eliminar horario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Horarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Horario eliminado correctamente"),
                new OA\Response(response: 404, description: "Horario no encontrado"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/schedules/{id}/restore",
            summary: "Restaurar horario eliminado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Horarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Horario restaurado correctamente"),
                new OA\Response(response: 404, description: "Horario no encontrado en papelera"),
                new OA\Response(response: 409, description: "Ya existe un horario activo para ese día"),
            ],
        ),
    ]
    public function restore(): void {}
}
