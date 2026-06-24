<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Parameter\StoreParameterRequest;
use App\Http\Requests\Parameter\UpdateParameterRequest;
use App\Http\Resources\ParameterResource;
use App\Http\Responses\ApiResult;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;

class ParameterController extends ApiController
{
    public function __construct(private readonly ParameterService $parameterService) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(ParameterResource::collection($this->parameterService->getAll()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $parameter = $this->parameterService->find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        return ApiResult::success(new ParameterResource($parameter))->toResponse();
    }

    public function store(StoreParameterRequest $request): JsonResponse
    {
        $parameter = $this->parameterService->create($request->validated());

        return ApiResult::created(new ParameterResource($parameter), "Parámetro creado correctamente.")->toResponse();
    }

    public function update(UpdateParameterRequest $request, int $id): JsonResponse
    {
        $parameter = $this->parameterService->find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        $this->parameterService->update($parameter, $request->validated());

        return ApiResult::success(
            new ParameterResource($parameter),
            "Parámetro actualizado correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $parameter = $this->parameterService->find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        $this->parameterService->delete($parameter);

        return ApiResult::empty("Parámetro eliminado correctamente.")->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(ParameterResource::collection($this->parameterService->getTrashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $parameter = $this->parameterService->findTrashed($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        $this->parameterService->restore($parameter);

        return ApiResult::empty("Parámetro restaurado correctamente.")->toResponse();
    }
}
