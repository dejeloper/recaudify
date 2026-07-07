<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\MenuItem\StoreMenuItemRequest;
use App\Http\Requests\MenuItem\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Http\Responses\ApiResult;
use App\Services\LoggingService;
use App\Services\MenuItemService;
use Illuminate\Http\JsonResponse;

class MenuItemController extends ApiController
{
    public function __construct(
        private readonly MenuItemService $menuItemService,
        private readonly LoggingService $logging,
    ) {}

    public function mine(): JsonResponse
    {
        return ApiResult::success(MenuItemResource::collection($this->menuItemService->tree()))->toResponse();
    }

    public function index(): JsonResponse
    {
        return ApiResult::success(MenuItemResource::collection($this->menuItemService->all()))->toResponse();
    }

    public function show(int $id): JsonResponse
    {
        $menuItem = $this->menuItemService->find($id);

        if (!$menuItem) {
            return ApiResult::notFound("Ítem de menú no encontrado.")->toResponse();
        }

        return ApiResult::success(new MenuItemResource($menuItem))->toResponse();
    }

    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        $menuItem = $this->menuItemService->create($request->validated());

        $this->logging->logBusiness("Ítem de menú creado", [
            "menu_item_id" => $menuItem->id,
            "label" => $menuItem->label,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::created(new MenuItemResource($menuItem), "Ítem de menú creado correctamente.")->toResponse();
    }

    public function update(UpdateMenuItemRequest $request, int $id): JsonResponse
    {
        $menuItem = $this->menuItemService->find($id);

        if (!$menuItem) {
            return ApiResult::notFound("Ítem de menú no encontrado.")->toResponse();
        }

        $updated = $this->menuItemService->update($menuItem, $request->validated());

        $this->logging->logBusiness("Ítem de menú actualizado", [
            "menu_item_id" => $updated->id,
            "label" => $updated->label,
            "by_user_id" => $request->user()?->id,
        ]);

        return ApiResult::success(
            new MenuItemResource($updated),
            "Ítem de menú actualizado correctamente.",
        )->toResponse();
    }

    public function destroy(int $id): JsonResponse
    {
        $menuItem = $this->menuItemService->find($id);

        if (!$menuItem) {
            return ApiResult::notFound("Ítem de menú no encontrado.")->toResponse();
        }

        $this->menuItemService->delete($menuItem);

        $this->logging->logBusiness("Ítem de menú eliminado", [
            "menu_item_id" => $menuItem->id,
            "label" => $menuItem->label,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Ítem de menú eliminado correctamente.")->toResponse();
    }

    public function trashed(): JsonResponse
    {
        return ApiResult::success(MenuItemResource::collection($this->menuItemService->trashed()))->toResponse();
    }

    public function restore(int $id): JsonResponse
    {
        $menuItem = $this->menuItemService->findTrashed($id);

        if (!$menuItem) {
            return ApiResult::notFound("Ítem de menú no encontrado.")->toResponse();
        }

        $restored = $this->menuItemService->restore($menuItem);

        $this->logging->logBusiness("Ítem de menú restaurado", [
            "menu_item_id" => $restored->id,
            "label" => $restored->label,
            "by_user_id" => request()->user()?->id,
        ]);

        return ApiResult::empty("Ítem de menú restaurado correctamente.")->toResponse();
    }
}
