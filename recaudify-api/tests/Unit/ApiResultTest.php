<?php

namespace Tests\Unit;

use App\Http\Responses\ApiResult;
use Tests\TestCase;

class ApiResultTest extends TestCase
{
    public function test_success_has_correct_properties(): void
    {
        $result = ApiResult::success(['id' => 1], 'OK');

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('OK', $result->message);
        $this->assertSame(['id' => 1], $result->data);
    }

    public function test_created_has_201_status(): void
    {
        $result = ApiResult::created(['id' => 1], 'Creado.');

        $this->assertTrue($result->success);
        $this->assertSame(201, $result->statusCode);
        $this->assertSame('Creado.', $result->message);
    }

    public function test_failure_has_correct_properties(): void
    {
        $result = ApiResult::failure('Algo falló.', 400);

        $this->assertFalse($result->success);
        $this->assertSame(400, $result->statusCode);
        $this->assertSame('Algo falló.', $result->message);
        $this->assertNull($result->data);
    }

    public function test_unauthorized_has_401_status(): void
    {
        $result = ApiResult::unauthorized('No autorizado.');

        $this->assertFalse($result->success);
        $this->assertSame(401, $result->statusCode);
    }

    public function test_not_found_has_404_status(): void
    {
        $result = ApiResult::notFound('No encontrado.');

        $this->assertFalse($result->success);
        $this->assertSame(404, $result->statusCode);
    }

    public function test_forbidden_has_403_status(): void
    {
        $result = ApiResult::forbidden('Sin permisos.');

        $this->assertFalse($result->success);
        $this->assertSame(403, $result->statusCode);
    }

    public function test_empty_has_null_data(): void
    {
        $result = ApiResult::empty('Listo.');

        $this->assertTrue($result->success);
        $this->assertNull($result->data);
    }

    public function test_to_response_returns_correct_json_structure(): void
    {
        $result   = ApiResult::success(['id' => 1], 'OK');
        $response = $result->toResponse();

        $this->assertSame(200, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('OK', $body['message']);
        $this->assertSame(200, $body['statusCode']);
        $this->assertSame(['id' => 1], $body['data']);
    }
}
