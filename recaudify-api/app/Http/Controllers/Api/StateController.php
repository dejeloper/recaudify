<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\State\StoreStateRequest;
use App\Http\Requests\State\UpdateStateRequest;
use App\Http\Resources\StateResource;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use App\Services\StateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateController extends ApiController
{
    public function __construct(
        private readonly StateService $stateService,
        private readonly LoggingService $logging,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $states = $this->stateService->all($request->query("entity"));

        return ApiResult::success(StateResource::collection($states))->toResponse();
    }

    /** Entidades que hoy tienen ciclo de vida configurado, para poblar el selector. */
    public function entities(): JsonResponse
    {
        return ApiResult::success($this->stateService->entities())->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $state = $this->stateService->find($id);

        if (!$state) {
            return ApiResult::notFound("Estado no encontrado.")->toResponse();
        }

        return ApiResult::success(new StateResource($state))->toResponse();
    }

    public function store(StoreStateRequest $request): JsonResponse
    {
        $state = $this->stateService->create($request->validated());

        $this->logging->logBusiness("Estado creado", [
            "state_id" => $state->id,
            "entity" => $state->entity,
            "key" => $state->key,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::created(new StateResource($state), "Estado creado correctamente.")->toResponse();
    }

    public function update(UpdateStateRequest $request, int $id): JsonResponse
    {
        $state = $this->stateService->find($id);

        if (!$state) {
            return ApiResult::notFound("Estado no encontrado.")->toResponse();
        }

        $updated = $this->stateService->update($state, $request->validated());

        $this->logging->logBusiness("Estado actualizado", [
            "state_id" => $updated->id,
            "entity" => $updated->entity,
            "key" => $updated->key,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(new StateResource($updated), "Estado actualizado correctamente.")->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $state = $this->stateService->find($id);

        if (!$state) {
            return ApiResult::notFound("Estado no encontrado.")->toResponse();
        }

        $this->stateService->delete($state);

        $this->logging->logBusiness("Estado eliminado", [
            "state_id" => $state->id,
            "entity" => $state->entity,
            "key" => $state->key,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Estado eliminado correctamente.")->toResponse();
    }

    public function trashed(Request $request): JsonResponse
    {
        $states = $this->stateService->trashed($request->query("entity"));

        return ApiResult::success(StateResource::collection($states))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $state = $this->stateService->findTrashed($id);

        if (!$state) {
            return ApiResult::notFound("Estado no encontrado.")->toResponse();
        }

        $restored = $this->stateService->restore($state);

        $this->logging->logBusiness("Estado restaurado", [
            "state_id" => $restored->id,
            "entity" => $restored->entity,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Estado restaurado correctamente.")->toResponse();
    }
}
