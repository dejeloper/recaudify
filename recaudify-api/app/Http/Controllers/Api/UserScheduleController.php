<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Schedule\StoreUserScheduleRequest;
use App\Http\Requests\Schedule\UpdateUserScheduleRequest;
use App\Http\Resources\UserScheduleResource;
use App\Http\Responses\ApiResult;
use App\Services\UserScheduleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserScheduleController extends ApiController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserScheduleService $scheduleService,
    ) {}

    public function index(int $userId): JsonResponse
    {
        $user = $this->userService->find($userId);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        return ApiResult::success(
            UserScheduleResource::collection($this->scheduleService->getForUser($user)),
        )->toResponse();
    }

    public function store(StoreUserScheduleRequest $request, int $userId): JsonResponse
    {
        $user = $this->userService->find($userId);

        if (!$user) {
            return ApiResult::notFound("Usuario no encontrado.")->toResponse();
        }

        if ($this->scheduleService->isDuplicateDay($user, $request->day_of_week)) {
            return ApiResult::failure("Ya existe un horario para ese dia.", 409)->toResponse();
        }

        $schedule = $this->scheduleService->create($user, $request->validated());

        return ApiResult::created(new UserScheduleResource($schedule), "Horario creado correctamente.")->toResponse();
    }

    public function update(UpdateUserScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = $this->scheduleService->find($id);

        if (!$schedule) {
            return ApiResult::notFound("Horario no encontrado.")->toResponse();
        }

        $this->scheduleService->update($schedule, $request->validated());

        return ApiResult::success(
            new UserScheduleResource($schedule),
            "Horario actualizado correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->find($id);

        if (!$schedule) {
            return ApiResult::notFound("Horario no encontrado.")->toResponse();
        }

        $this->scheduleService->delete($schedule);

        return ApiResult::empty("Horario eliminado correctamente.")->toResponse();
    }
}
