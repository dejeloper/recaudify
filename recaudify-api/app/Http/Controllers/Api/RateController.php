<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Rate\StoreRateRequest;
use App\Http\Requests\Rate\UpdateRateRequest;
use App\Http\Resources\RateResource;
use App\Http\Responses\ApiResult;
use App\Services\RateService;
use Illuminate\Http\JsonResponse;

class RateController extends ApiController
{
    public function __construct(private readonly RateService $rateService) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(RateResource::collection($this->rateService->getAll()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $rate = $this->rateService->find($id);

        if (!$rate) {
            return ApiResult::notFound("Tarifa no encontrada.")->toResponse();
        }

        return ApiResult::success(new RateResource($rate))->toResponse();
    }

    public function store(StoreRateRequest $request): JsonResponse
    {
        $rate = $this->rateService->create($request->validated());

        return ApiResult::created(new RateResource($rate), "Tarifa creada correctamente.")->toResponse();
    }

    public function update(UpdateRateRequest $request, int $id): JsonResponse
    {
        $rate = $this->rateService->find($id);

        if (!$rate) {
            return ApiResult::notFound("Tarifa no encontrada.")->toResponse();
        }

        $this->rateService->update($rate, $request->validated());

        return ApiResult::success(
            new RateResource($rate->fresh("product")),
            "Tarifa actualizada correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $rate = $this->rateService->find($id);

        if (!$rate) {
            return ApiResult::notFound("Tarifa no encontrada.")->toResponse();
        }

        $this->rateService->delete($rate);

        return ApiResult::empty("Tarifa eliminada correctamente.")->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(RateResource::collection($this->rateService->getTrashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $rate = $this->rateService->findTrashed($id);

        if (!$rate) {
            return ApiResult::notFound("Tarifa no encontrada.")->toResponse();
        }

        $this->rateService->restore($rate);

        return ApiResult::empty("Tarifa restaurada correctamente.")->toResponse();
    }
}
