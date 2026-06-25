<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResult;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends ApiController
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(): JsonResponse
    {
        return ApiResult::success(ProductResource::collection($this->productService->getAll()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (! $product) {
            return ApiResult::notFound('Producto no encontrado.')->toResponse();
        }

        return ApiResult::success(new ProductResource($product))->toResponse();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return ApiResult::created(new ProductResource($product), 'Producto creado correctamente.')->toResponse();
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (! $product) {
            return ApiResult::notFound('Producto no encontrado.')->toResponse();
        }

        $this->productService->update($product, $request->validated());

        return ApiResult::success(new ProductResource($product), 'Producto actualizado correctamente.')->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (! $product) {
            return ApiResult::notFound('Producto no encontrado.')->toResponse();
        }

        $this->productService->delete($product);

        return ApiResult::empty('Producto eliminado correctamente.')->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(ProductResource::collection($this->productService->getTrashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $product = $this->productService->findTrashed($id);

        if (! $product) {
            return ApiResult::notFound('Producto no encontrado.')->toResponse();
        }

        $this->productService->restore($product);

        return ApiResult::empty('Producto restaurado correctamente.')->toResponse();
    }
}
