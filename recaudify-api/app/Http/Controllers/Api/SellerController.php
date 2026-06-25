<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Seller\StoreSellerRequest;
use App\Http\Requests\Seller\UpdateSellerRequest;
use App\Http\Resources\SellerResource;
use App\Http\Responses\ApiResult;
use App\Services\SellerService;
use Illuminate\Http\JsonResponse;

class SellerController extends ApiController
{
    public function __construct(private readonly SellerService $sellerService) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(SellerResource::collection($this->sellerService->getAll()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $seller = $this->sellerService->find($id);

        if (! $seller) {
            return ApiResult::notFound('Vendedor no encontrado.')->toResponse();
        }

        return ApiResult::success(new SellerResource($seller))->toResponse();
    }

    public function store(StoreSellerRequest $request): JsonResponse
    {
        $seller = $this->sellerService->create($request->validated());

        return ApiResult::created(new SellerResource($seller), 'Vendedor creado correctamente.')->toResponse();
    }

    public function update(UpdateSellerRequest $request, int $id): JsonResponse
    {
        $seller = $this->sellerService->find($id);

        if (! $seller) {
            return ApiResult::notFound('Vendedor no encontrado.')->toResponse();
        }

        $this->sellerService->update($seller, $request->validated());

        return ApiResult::success(new SellerResource($seller), 'Vendedor actualizado correctamente.')->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $seller = $this->sellerService->find($id);

        if (! $seller) {
            return ApiResult::notFound('Vendedor no encontrado.')->toResponse();
        }

        $this->sellerService->delete($seller);

        return ApiResult::empty('Vendedor eliminado correctamente.')->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(SellerResource::collection($this->sellerService->getTrashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $seller = $this->sellerService->findTrashed($id);

        if (! $seller) {
            return ApiResult::notFound('Vendedor no encontrado.')->toResponse();
        }

        $this->sellerService->restore($seller);

        return ApiResult::empty('Vendedor restaurado correctamente.')->toResponse();
    }
}
