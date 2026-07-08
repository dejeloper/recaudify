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

        if (!$request->filled("page")) {
            return ApiResult::success(ParameterResource::collection($this->parameterService->all($type)))->toResponse();
        }

        $search = $request->filled("search") ? $request->string("search")->toString() : null;

        $defaultPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_per_page");
        $maxPerPage = (int) $this->parameterService->get(ParameterType::Application, "pagination_max_per_page");

        $perPage = (int) $request->query("per_page", (string) $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $paginator = $this->parameterService->paginate($type, $search, $perPage);

        return ApiResult::paginated(
            $paginator,
            ParameterResource::collection($paginator->getCollection()),
        )->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $parameter = $this->parameterService->find($id);

        if (!$parameter) {
            return ApiResult::notFound("Parametro no encontrado.")->toResponse();
        }

        return ApiResult::success(new ParameterResource($parameter))->toResponse();
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

    public function trashed(): JsonResponse
    {
        return ApiResult::success(ParameterResource::collection($this->parameterService->trashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $parameter = $this->parameterService->findTrashed($id);

        if (!$parameter) {
            return ApiResult::notFound("Parametro no encontrado.")->toResponse();
        }

        $restored = $this->parameterService->restore($parameter);

        $this->logging->logBusiness("Parametro restaurado", [
            "parameter_id" => $restored->id,
            "type" => $restored->type->value,
            "key" => $restored->key,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Parametro restaurado.")->toResponse();
    }
}
