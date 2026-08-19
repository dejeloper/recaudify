<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\State\StoreStateTransitionRequest;
use App\Http\Requests\State\UpdateStateTransitionRequest;
use App\Http\Resources\StateTransitionResource;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use App\Services\StateTransitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StateTransitionController extends ApiController
{
    public function __construct(
        private readonly StateTransitionService $transitionService,
        private readonly LoggingService $logging,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $transitions = $this->transitionService->all($request->query("entity"));

        return ApiResult::success(StateTransitionResource::collection($transitions))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $transition = $this->transitionService->find($id);

        if (!$transition) {
            return ApiResult::notFound("Transición no encontrada.")->toResponse();
        }

        return ApiResult::success(new StateTransitionResource($transition))->toResponse();
    }

    public function store(StoreStateTransitionRequest $request): JsonResponse
    {
        $transition = $this->transitionService->create($request->validated());

        $this->logging->logBusiness("Transición creada", [
            "transition_id" => $transition->id,
            "entity" => $transition->entity,
            "from" => $transition->fromState?->key,
            "to" => $transition->toState?->key,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::created(
            new StateTransitionResource($transition),
            "Transición creada correctamente.",
        )->toResponse();
    }

    public function update(UpdateStateTransitionRequest $request, int $id): JsonResponse
    {
        $transition = $this->transitionService->find($id);

        if (!$transition) {
            return ApiResult::notFound("Transición no encontrada.")->toResponse();
        }

        $updated = $this->transitionService->update($transition, $request->validated());

        $this->logging->logBusiness("Transición actualizada", [
            "transition_id" => $updated->id,
            "entity" => $updated->entity,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(
            new StateTransitionResource($updated),
            "Transición actualizada correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $transition = $this->transitionService->find($id);

        if (!$transition) {
            return ApiResult::notFound("Transición no encontrada.")->toResponse();
        }

        $this->transitionService->delete($transition);

        $this->logging->logBusiness("Transición eliminada", [
            "transition_id" => $transition->id,
            "entity" => $transition->entity,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Transición eliminada correctamente.")->toResponse();
    }

    public function trashed(Request $request): JsonResponse
    {
        $transitions = $this->transitionService->trashed($request->query("entity"));

        return ApiResult::success(StateTransitionResource::collection($transitions))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $transition = $this->transitionService->findTrashed($id);

        if (!$transition) {
            return ApiResult::notFound("Transición no encontrada.")->toResponse();
        }

        $restored = $this->transitionService->restore($transition);

        $this->logging->logBusiness("Transición restaurada", [
            "transition_id" => $restored->id,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Transición restaurada correctamente.")->toResponse();
    }
}
