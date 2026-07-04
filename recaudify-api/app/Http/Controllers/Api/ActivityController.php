<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Resources\ActivityResource;
use App\Http\Responses\ApiResult;
use App\Services\ActivityService;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends ApiController
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly ParameterService $parameterService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            "log_name" => $request->query("log_name"),
            "causer_id" => $request->query("causer_id"),
            "user" => $request->query("user"),
            "model" => $request->query("model"),
            "subject_id" => $request->query("subject_id"),
        ];

        $defaultPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_per_page");
        $maxPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_max_per_page");

        $perPage = (int) $request->query("per_page", (string) $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $paginator = $this->activityService->getAll($filters, $perPage);

        return ApiResult::paginated(
            $paginator,
            ActivityResource::collection($paginator->getCollection()),
        )->toResponse();
    }
}
