<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\LoginAuditResource;
use App\Http\Responses\ApiResult;
use App\Services\LoginAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginAuditController extends ApiController
{
    public function __construct(private readonly LoginAuditService $loginAudit) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'user_id' => $request->query('user_id'),
            'status' => $request->query('status'),
        ];

        $perPage = (int) $request->query('per_page', (string) LoginAuditService::DEFAULT_PER_PAGE);
        $perPage = max(1, min($perPage, LoginAuditService::MAX_PER_PAGE));

        $paginator = $this->loginAudit->getAll($filters, $perPage);

        return ApiResult::paginated(
            $paginator,
            LoginAuditResource::collection($paginator->getCollection()),
        )->toResponse();
    }
}
