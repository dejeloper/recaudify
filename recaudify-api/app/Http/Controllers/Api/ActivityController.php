<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Requests\Activity\IndexActivityRequest;
use App\Http\Requests\Activity\PurgeActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Responses\ApiResult;
use App\Services\ActivityService;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;

class ActivityController extends ApiController
{
    public function __construct(
        private readonly ActivityService $activityService,
        private readonly ParameterService $parameterService,
    ) {}

    public function index(IndexActivityRequest $request): JsonResponse
    {
        $filters = $request->only(["log_name", "causer_id", "user", "model", "subject_id", "from", "to"]);

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

    /** Cuántos registros eliminaría la purga, sin borrar nada. */
    public function purgePreview(PurgeActivityRequest $request): JsonResponse
    {
        $result = $this->activityService->previewPurge($request->integer("days") ?: null);

        return ApiResult::success($result)->toResponse();
    }

    /** Única vía de borrado del log. La purga queda registrada como actividad. */
    public function purge(PurgeActivityRequest $request): JsonResponse
    {
        $result = $this->activityService->purge($request->integer("days") ?: null);

        return ApiResult::success($result, "Log purgado correctamente.")->toResponse();
    }
}
