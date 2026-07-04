<?php

namespace App\Http\Controllers\Api;

use App\Enums\ParameterType;
use App\Http\Requests\Parameter\StoreParameterRequest;
use App\Http\Requests\Parameter\UpdateParameterRequest;
use App\Http\Resources\ParameterResource;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use App\Services\ParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParameterController extends ApiController
{
    public function __construct(
        private readonly ParameterService $parameterService,
        private readonly LoggingService $logging,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $type = $request->filled("type") ? ParameterType::tryFrom($request->type) : null;

        return ApiResult::success(ParameterResource::collection($this->parameterService->all($type)))->toResponse();
    }

    public function store(StoreParameterRequest $request): JsonResponse
    {
        $parameter = $this->parameterService->create($request->validated());

        $this->logging->logBusiness("Parametro creado", [
            "parameter_id" => $parameter->id,
            "type" => $parameter->type->value,
            "key" => $parameter->key,
            "value" => $parameter->value,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::created(new ParameterResource($parameter), "Parametro creado correctamente.")->toResponse();
    }

    public function update(UpdateParameterRequest $request, int $id): JsonResponse
    {
        $parameter = $this->parameterService->findOrFail($id);

        if (!$parameter->is_editable) {
            return ApiResult::forbidden("Este parametro no es editable.")->toResponse();
        }

        $previous = $parameter->value;
        $updated = $this->parameterService->update($parameter, $request->value);

        $this->logging->logBusiness("Parametro actualizado", [
            "parameter_id" => $updated->id,
            "type" => $updated->type->value,
            "key" => $updated->key,
            "previous_value" => $previous,
            "new_value" => $updated->value,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(new ParameterResource($updated), "Parametro actualizado.")->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $parameter = $this->parameterService->findOrFail($id);

        $this->parameterService->delete($parameter);

        $this->logging->logBusiness("Parametro eliminado", [
            "parameter_id" => $parameter->id,
            "type" => $parameter->type->value,
            "key" => $parameter->key,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Parametro eliminado.")->toResponse();
    }
}
