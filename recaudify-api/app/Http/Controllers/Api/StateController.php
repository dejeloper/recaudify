<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\StateResource;
use App\Http\Responses\ApiResult;
use App\Services\StateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends ApiController
{
    public function __construct(private readonly StateService $stateService) {}

    public function index(Request $request): JsonResponse
    {
        $type = $request->query('type');

        return ApiResult::success(StateResource::collection($this->stateService->getAll($type)))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $state = $this->stateService->find($id);

        if (! $state) {
            return ApiResult::notFound('Estado no encontrado.')->toResponse();
        }

        return ApiResult::success(new StateResource($state))->toResponse();
    }
}
