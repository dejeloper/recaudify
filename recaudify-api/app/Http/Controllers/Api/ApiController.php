<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Recaudify API',
    version: '1.0.0',
    description: 'API REST para la gestión de clientes, cartera y cobranza.',
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Servidor activo',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
class ApiController extends Controller {}
