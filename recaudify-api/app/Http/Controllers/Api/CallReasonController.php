<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CallReason\StoreCallReasonRequest;
use App\Http\Requests\CallReason\UpdateCallReasonRequest;
use App\Http\Resources\CallReasonResource;
use App\Http\Responses\ApiResult;
use App\Services\CallReasonService;
use Illuminate\Http\JsonResponse;

class CallReasonController extends ApiController
{
    public function __construct(private readonly CallReasonService $callReasonService) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(CallReasonResource::collection($this->callReasonService->getAll()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $callReason = $this->callReasonService->find($id);

        if (! $callReason) {
            return ApiResult::notFound('Motivo de llamada no encontrado.')->toResponse();
        }

        return ApiResult::success(new CallReasonResource($callReason))->toResponse();
    }

    public function store(StoreCallReasonRequest $request): JsonResponse
    {
        $callReason = $this->callReasonService->create($request->validated());

        return ApiResult::created(new CallReasonResource($callReason), 'Motivo de llamada creado correctamente.')->toResponse();
    }

    public function update(UpdateCallReasonRequest $request, int $id): JsonResponse
    {
        $callReason = $this->callReasonService->find($id);

        if (! $callReason) {
            return ApiResult::notFound('Motivo de llamada no encontrado.')->toResponse();
        }

        $this->callReasonService->update($callReason, $request->validated());

        return ApiResult::success(new CallReasonResource($callReason), 'Motivo de llamada actualizado correctamente.')->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $callReason = $this->callReasonService->find($id);

        if (! $callReason) {
            return ApiResult::notFound('Motivo de llamada no encontrado.')->toResponse();
        }

        $this->callReasonService->delete($callReason);

        return ApiResult::empty('Motivo de llamada eliminado correctamente.')->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(CallReasonResource::collection($this->callReasonService->getTrashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $callReason = $this->callReasonService->findTrashed($id);

        if (! $callReason) {
            return ApiResult::notFound('Motivo de llamada no encontrado.')->toResponse();
        }

        $this->callReasonService->restore($callReason);

        return ApiResult::empty('Motivo de llamada restaurado correctamente.')->toResponse();
    }
}
