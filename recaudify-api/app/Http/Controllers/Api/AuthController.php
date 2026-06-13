<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Registrar usuario',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'username', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name',                  type: 'string',  example: 'Juan Pérez'),
                    new OA\Property(property: 'username',              type: 'string',  example: 'jperez'),
                    new OA\Property(property: 'email',                 type: 'string',  format: 'email', example: 'jperez@empresa.com', nullable: true),
                    new OA\Property(property: 'password',              type: 'string',  format: 'password', example: 'secret1234'),
                    new OA\Property(property: 'password_confirmation', type: 'string',  format: 'password', example: 'secret1234'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario registrado correctamente.'),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id',       type: 'integer', example: 1),
                            new OA\Property(property: 'name',     type: 'string',  example: 'Juan Pérez'),
                            new OA\Property(property: 'username', type: 'string',  example: 'jperez'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'user'    => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Iniciar sesión',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'admin'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'admin1234'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token',      type: 'string',  example: 'eyJ0eXAiOiJKV1Qi...'),
                        new OA\Property(property: 'token_type', type: 'string',  example: 'bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 900),
                        new OA\Property(property: 'user', type: 'object', properties: [
                            new OA\Property(property: 'id',       type: 'integer', example: 1),
                            new OA\Property(property: 'name',     type: 'string',  example: 'Administrador'),
                            new OA\Property(property: 'username', type: 'string',  example: 'admin'),
                            new OA\Property(property: 'role',     type: 'string',  example: 'administrador'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credenciales incorrectas'),
            new OA\Response(response: 403, description: 'Usuario inactivo'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (! $token = $this->guard()->attempt($credentials)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        /** @var User $user */
        $user = $this->guard()->user();

        if (! $user->active) {
            $this->guard()->logout();

            return response()->json(['message' => 'Usuario inactivo.'], 403);
        }

        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'       => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'role'     => $user->getRoleNames()->first(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Obtener usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Datos del usuario autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id',          type: 'integer', example: 1),
                        new OA\Property(property: 'name',        type: 'string',  example: 'Administrador'),
                        new OA\Property(property: 'username',    type: 'string',  example: 'admin'),
                        new OA\Property(property: 'email',       type: 'string',  nullable: true, example: null),
                        new OA\Property(property: 'roles',       type: 'array',   items: new OA\Items(type: 'string', example: 'administrador')),
                        new OA\Property(property: 'permissions', type: 'array',   items: new OA\Items(type: 'string', example: 'clientes.ver')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        return response()->json([
            'id'          => $user->id,
            'name'        => $user->name,
            'username'    => $user->username,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sesión cerrada correctamente.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth('api');
    }
}
