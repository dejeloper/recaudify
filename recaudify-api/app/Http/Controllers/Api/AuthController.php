<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResult;
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
            new OA\Response(response: 201, description: 'Usuario registrado correctamente'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return ApiResult::created([
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
        ], 'Usuario registrado correctamente.')->toResponse();
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
            new OA\Response(response: 200, description: 'Login exitoso'),
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
            return ApiResult::unauthorized('Credenciales incorrectas.')->toResponse();
        }

        /** @var User $user */
        $user = $this->guard()->user();

        if (! $user->active) {
            $this->guard()->logout();

            return ApiResult::forbidden('Usuario inactivo.')->toResponse();
        }

        return ApiResult::success([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'       => [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'role'     => $user->getRoleNames()->first(),
            ],
        ], 'Sesión iniciada correctamente.')->toResponse();
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Obtener usuario autenticado',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Datos del usuario autenticado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->guard()->user();

        return ApiResult::success(new UserResource($user))->toResponse();
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Cerrar sesión',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return ApiResult::empty('Sesión cerrada correctamente.')->toResponse();
    }

    private function guard(): JWTGuard
    {
        /** @var JWTGuard */
        return auth('api');
    }
}
