<?php

namespace App\OpenApi\Auth;

use OpenApi\Attributes as OA;

class AuthDocs
{
    #[
        OA\Post(
            path: "/api/auth/register",
            summary: "Registrar usuario",
            tags: ["Auth"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["name", "username", "password", "password_confirmation"],
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Juan Pérez"),
                        new OA\Property(property: "username", type: "string", example: "jperez"),
                        new OA\Property(
                            property: "email",
                            type: "string",
                            format: "email",
                            example: "jperez@empresa.com",
                            nullable: true,
                        ),
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
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 201, description: "Usuario registrado correctamente"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function register(): void {}

    #[
        OA\Post(
            path: "/api/auth/login",
            summary: "Iniciar sesión",
            tags: ["Auth"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["username", "password"],
                    properties: [
                        new OA\Property(property: "username", type: "string", example: "admin"),
                        new OA\Property(property: "password", type: "string", format: "password", example: "admin1234"),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Login exitoso"),
                new OA\Response(response: 401, description: "Credenciales incorrectas"),
                new OA\Response(response: 403, description: "Usuario inactivo o fuera de horario"),
            ],
        ),
    ]
    public function login(): void {}

    #[
        OA\Post(
            path: "/api/auth/login/location",
            summary: "Registrar geolocalización del acceso",
            description: "Enriquece el último acceso exitoso del usuario con su geolocalización (la captura el cliente tras el login).",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auth"],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["latitude", "longitude"],
                    properties: [
                        new OA\Property(property: "latitude", type: "number", format: "float", example: 4.711),
                        new OA\Property(property: "longitude", type: "number", format: "float", example: -74.0721),
                        new OA\Property(
                            property: "accuracy",
                            type: "number",
                            format: "float",
                            nullable: true,
                            example: 12.5,
                        ),
                    ],
                ),
            ),
            responses: [
                new OA\Response(response: 200, description: "Ubicación registrada"),
                new OA\Response(response: 401, description: "No autenticado"),
                new OA\Response(response: 422, description: "Error de validación"),
            ],
        ),
    ]
    public function loginLocation(): void {}

    #[
        OA\Get(
            path: "/api/auth/me",
            summary: "Obtener usuario autenticado",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auth"],
            responses: [
                new OA\Response(response: 200, description: "Datos del usuario autenticado"),
                new OA\Response(response: 401, description: "No autenticado"),
            ],
        ),
    ]
    public function me(): void {}

    #[
        OA\Post(
            path: "/api/auth/refresh",
            summary: "Renovar token de sesión",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auth"],
            responses: [
                new OA\Response(response: 200, description: "Token renovado"),
                new OA\Response(response: 401, description: "Sesión expirada o inválida"),
            ],
        ),
    ]
    public function refresh(): void {}

    #[
        OA\Post(
            path: "/api/auth/logout",
            summary: "Cerrar sesión",
            security: [["bearerAuth" => []], ["cookieAuth" => []]],
            tags: ["Auth"],
            responses: [
                new OA\Response(response: 200, description: "Sesión cerrada correctamente"),
                new OA\Response(response: 401, description: "No autenticado"),
            ],
        ),
    ]
    public function logout(): void {}
}
