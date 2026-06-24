<?php

namespace App\OpenApi\User;

use OpenApi\Attributes as OA;

class UserDocs
{
    #[
        OA\Get(
            path: "/api/users",
            summary: "Listar usuarios activos",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            responses: [
                new OA\Response(response: 200, description: "Lista de usuarios activos"),
                new OA\Response(response: 401, description: "No autenticado"),
            ],
        ),
    ]
    public function index(): void {}

    #[
        OA\Get(
            path: "/api/users/disabled",
            summary: "Listar usuarios desactivados (soft delete)",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            responses: [
                new OA\Response(response: 200, description: "Lista de usuarios desactivados"),
                new OA\Response(response: 401, description: "No autenticado"),
            ],
        ),
    ]
    public function indexDisabled(): void {}

    #[
        OA\Get(
            path: "/api/users/{id}",
            summary: "Obtener usuario por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Usuario encontrado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function show(): void {}

    #[
        OA\Get(
            path: "/api/users/trashed/{id}",
            summary: "Obtener usuario eliminado por ID",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Usuario eliminado encontrado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function showTrashed(): void {}

    #[
        OA\Get(
            path: "/api/users/search/{name}",
            summary: "Buscar usuarios por nombre",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "name", in: "path", required: true, schema: new OA\Schema(type: "string")),
            ],
            responses: [new OA\Response(response: 200, description: "Usuarios encontrados")],
        ),
    ]
    public function search(): void {}

    #[
        OA\Post(
            path: "/api/users",
            summary: "Crear usuario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name", "username", "password", "password_confirmation"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Juan Pérez"),
                        new OA\Property(property: "username", type: "string", example: "jperez"),
                        new OA\Property(property: "email", type: "string", nullable: true, example: null),
                        new OA\Property(
                            property: "password",
                            type: "string",
                            format: "password",
                            example: "secret1234",
                        ),
                        new OA\Property(
                            property: "password_confirmation",
                            type: "string",
                            format: "password",
                            example: "secret1234",
                        ),
                        new OA\Property(property: "role", type: "string", nullable: true, example: "cobrador"),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Usuario creado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function store(): void {}

    #[
        OA\Put(
            path: "/api/users/{id}",
            summary: "Actualizar usuario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Nuevo Nombre"),
                        new OA\Property(property: "username", type: "string", example: "nuevo_usuario"),
                        new OA\Property(property: "email", type: "string", nullable: true),
                        new OA\Property(property: "password", type: "string", format: "password", nullable: true),
                        new OA\Property(property: "role", type: "string", nullable: true, example: "cobrador"),
                        new OA\Property(property: "active", type: "boolean", example: true),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Usuario actualizado"),
                new OA\Response(response: 404, description: "No encontrado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function update(): void {}

    #[
        OA\Delete(
            path: "/api/users/{id}",
            summary: "Desactivar usuario (soft delete)",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Usuario desactivado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function destroy(): void {}

    #[
        OA\Post(
            path: "/api/users/{id}/restore",
            summary: "Restaurar usuario desactivado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            responses: [
                new OA\Response(response: 200, description: "Usuario restaurado"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function restore(): void {}

    #[
        OA\Post(
            path: "/api/users/{id}/permissions",
            summary: "Asignar o revocar permisos directos a un usuario",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Usuarios"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["permissions", "assign"],
                    properties: [
                        new OA\Property(
                            property: "permissions",
                            type: "array",
                            items: new OA\Items(type: "string", example: "clientes.ver"),
                        ),
                        new OA\Property(
                            property: "assign",
                            type: "boolean",
                            example: true,
                            description: "true para asignar, false para revocar",
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Permisos actualizados"),
                new OA\Response(response: 404, description: "No encontrado"),
            ],
        ),
    ]
    public function syncPermissions(): void {}
}
