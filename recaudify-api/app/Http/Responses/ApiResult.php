<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly int $statusCode,
        public readonly mixed $data = null,
    ) {}

    public static function success(mixed $data, string $message = 'Operación exitosa.'): self
    {
        return new self(true, $message, 200, $data);
    }

    public static function created(mixed $data, string $message = 'Creado correctamente.'): self
    {
        return new self(true, $message, 201, $data);
    }

    public static function empty(string $message = 'Operación exitosa.', int $statusCode = 200): self
    {
        return new self(true, $message, $statusCode, null);
    }

    /**
     * Respuesta paginada estándar: data = { items: [...], meta: {...} }.
     * $items son los elementos de la página (normalmente un Resource::collection).
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $items,
        string $message = 'Operación exitosa.',
    ): self {
        return new self(true, $message, 200, [
            'items' => $items,
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'lastPage' => $paginator->lastPage(),
            ],
        ]);
    }

    public static function failure(string $message, int $statusCode = 400): self
    {
        return new self(false, $message, $statusCode, null);
    }

    public static function notFound(string $message = 'No encontrado.'): self
    {
        return new self(false, $message, 404, null);
    }

    public static function unauthorized(string $message = 'No autorizado.'): self
    {
        return new self(false, $message, 401, null);
    }

    public static function forbidden(string $message = 'Sin permisos.'): self
    {
        return new self(false, $message, 403, null);
    }

    public static function validationError(array $errors, string $message = 'Error de validación.'): self
    {
        return new self(false, $message, 422, $errors);
    }

    public function toResponse(): JsonResponse
    {
        return response()->json(
            [
                'success' => $this->success,
                'message' => $this->message,
                'statusCode' => $this->statusCode,
                'data' => $this->data,
            ],
            $this->statusCode,
        );
    }
}
