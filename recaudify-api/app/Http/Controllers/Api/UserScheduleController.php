<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Schedule\StoreUserScheduleRequest;
use App\Http\Requests\Schedule\UpdateUserScheduleRequest;
use App\Http\Resources\UserScheduleResource;
use App\Http\Responses\ApiResult;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Http\JsonResponse;

class UserScheduleController extends ApiController
{
    public function index(int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return ApiResult::notFound('Usuario no encontrado.')->toResponse();
        }

        $schedules = $user->schedules()->orderBy('day_of_week')->get();

        return ApiResult::success(UserScheduleResource::collection($schedules))->toResponse();
    }

    public function store(StoreUserScheduleRequest $request, int $userId): JsonResponse
    {
        $user = User::find($userId);

        if (! $user) {
            return ApiResult::notFound('Usuario no encontrado.')->toResponse();
        }

        $exists = $user->schedules()
            ->where('day_of_week', $request->day_of_week)
            ->exists();

        if ($exists) {
            return ApiResult::failure('Ya existe un horario para ese día.', 409)->toResponse();
        }

        $schedule = $user->schedules()->create($request->validated());

        return ApiResult::created(new UserScheduleResource($schedule), 'Horario creado correctamente.')->toResponse();
    }

    public function update(UpdateUserScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = UserSchedule::find($id);

        if (! $schedule) {
            return ApiResult::notFound('Horario no encontrado.')->toResponse();
        }

        $schedule->update($request->validated());

        return ApiResult::success(new UserScheduleResource($schedule), 'Horario actualizado correctamente.')->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = UserSchedule::find($id);

        if (! $schedule) {
            return ApiResult::notFound('Horario no encontrado.')->toResponse();
        }

        $schedule->delete();

        return ApiResult::empty('Horario eliminado correctamente.')->toResponse();
    }
}
