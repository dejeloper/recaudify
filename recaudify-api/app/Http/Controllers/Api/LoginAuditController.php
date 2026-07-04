<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Resources\LoginAuditResource;
use App\Http\Responses\ApiResult;
use App\Services\LoginAuditService;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginAuditController extends ApiController
{
    public function __construct(
        private readonly LoginAuditService $loginAudit,
        private readonly ParameterService $parameterService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            "user_id" => $request->query("user_id"),
            "status" => $request->query("status"),
        ];

        $defaultPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_per_page");
        $maxPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_max_per_page");

        $perPage = (int) $request->query("per_page", (string) $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $paginator = $this->loginAudit->getAll($filters, $perPage);

        return ApiResult::paginated(
            $paginator,
            LoginAuditResource::collection($paginator->getCollection()),
        )->toResponse();
    }
}
