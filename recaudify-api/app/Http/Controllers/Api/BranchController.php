<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Http\Responses\ApiResult;
use App\Services\BranchService;
use App\Services\LoggingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends ApiController
{
    public function __construct(
        private readonly BranchService $branchService,
        private readonly LoggingService $logging,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $branches = $this->branchService->all($request->query("search"));

        return ApiResult::success(BranchResource::collection($branches))->toResponse();
    }

    public function main(): JsonResponse
    {
        $branch = $this->branchService->main();

        if (!$branch) {
            return ApiResult::notFound("No hay una sucursal principal configurada.")->toResponse();
        }

        return ApiResult::success(new BranchResource($branch))->toResponse();
    }

    public function trashed(Request $request): JsonResponse
    {
        $branches = $this->branchService->trashed($request->query("search"));

        return ApiResult::success(BranchResource::collection($branches))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $branch = $this->branchService->find($id);

        if (!$branch) {
            return ApiResult::notFound("Sucursal no encontrada.")->toResponse();
        }

        return ApiResult::success(new BranchResource($branch))->toResponse();
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->create($request->validated());

        $this->logging->logBusiness("Sucursal creada", [
            "branch_id" => $branch->id,
            "code" => $branch->code,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::created(new BranchResource($branch), "Sucursal creada correctamente.")->toResponse();
    }

    public function update(UpdateBranchRequest $request, int $id): JsonResponse
    {
        $branch = $this->branchService->find($id);

        if (!$branch) {
            return ApiResult::notFound("Sucursal no encontrada.")->toResponse();
        }

        $updated = $this->branchService->update($branch, $request->validated());

        $this->logging->logBusiness("Sucursal actualizada", [
            "branch_id" => $updated->id,
            "code" => $updated->code,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(new BranchResource($updated), "Sucursal actualizada correctamente.")->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $branch = $this->branchService->find($id);

        if (!$branch) {
            return ApiResult::notFound("Sucursal no encontrada.")->toResponse();
        }

        $this->branchService->delete($branch);

        $this->logging->logBusiness("Sucursal eliminada", [
            "branch_id" => $branch->id,
            "code" => $branch->code,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Sucursal eliminada correctamente.")->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $branch = $this->branchService->findTrashed($id);

        if (!$branch) {
            return ApiResult::notFound("Sucursal no encontrada.")->toResponse();
        }

        $restored = $this->branchService->restore($branch);

        $this->logging->logBusiness("Sucursal restaurada", [
            "branch_id" => $restored->id,
            "code" => $restored->code,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Sucursal restaurada correctamente.")->toResponse();
    }
}
