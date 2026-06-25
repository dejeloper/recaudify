<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ActivityResource;
use App\Http\Responses\ApiResult;
use App\Services\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends ApiController
{
    public function __construct(private readonly ActivityService $activityService) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            "log_name" => $request->query("log_name"),
            "causer_id" => $request->query("causer_id"),
            "model" => $request->query("model"),
            "subject_id" => $request->query("subject_id"),
        ];

        return ApiResult::success(ActivityResource::collection($this->activityService->getAll($filters)))->toResponse();
    }
}
