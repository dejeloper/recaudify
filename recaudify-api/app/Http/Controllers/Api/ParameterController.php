<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Parameter\StoreParameterRequest;
use App\Http\Requests\Parameter\UpdateParameterRequest;
use App\Http\Resources\ParameterResource;
use App\Http\Responses\ApiResult;
use App\Models\Parameter;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;

class ParameterController extends ApiController
{
    public function index(): JsonResponse
    {
        $parameters = Parameter::orderBy("key")->get();

        return ApiResult::success(ParameterResource::collection($parameters))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $parameter = Parameter::find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        return ApiResult::success(new ParameterResource($parameter))->toResponse();
    }

    public function store(StoreParameterRequest $request): JsonResponse
    {
        $parameter = Parameter::create($request->validated());

        ParameterService::clearCache();

        return ApiResult::created(new ParameterResource($parameter), "Parámetro creado correctamente.")->toResponse();
    }

    public function update(UpdateParameterRequest $request, int $id): JsonResponse
    {
        $parameter = Parameter::find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        $parameter->update($request->validated());

        ParameterService::clearCache();

        return ApiResult::success(
            new ParameterResource($parameter),
            "Parámetro actualizado correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $parameter = Parameter::find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        $parameter->delete();

        ParameterService::clearCache();

        return ApiResult::empty("Parámetro eliminado correctamente.")->toResponse();
    }

    public function trashed(): JsonResponse
    {
        $parameters = Parameter::onlyTrashed()->orderBy("key")->get();

        return ApiResult::success(ParameterResource::collection($parameters))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $parameter = Parameter::onlyTrashed()->find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parámetro no encontrado.")->toResponse();
        }

        $parameter->restore();

        ParameterService::clearCache();

        return ApiResult::empty("Parámetro restaurado correctamente.")->toResponse();
    }
}
