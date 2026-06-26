<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallReasonController;
use App\Http\Controllers\Api\LoginAuditController;
use App\Http\Controllers\Api\ParameterController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RateController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SellerController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix("auth")->group(function () {
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);
    Route::get("config", [AuthController::class, "config"]);

    Route::post("refresh", [AuthController::class, "refresh"])->middleware("throttle:10,1");

    Route::middleware("auth:api")->group(function () {
        Route::get("me", [AuthController::class, "me"]);
        Route::post("login/location", [AuthController::class, "loginLocation"]);
        Route::post("logout", [AuthController::class, "logout"]);
    });
});

Route::middleware(["auth:api", "check.schedule"])->group(function () {
    Route::prefix("users")->group(function () {
        Route::get("/", [UserController::class, "index"])->middleware("permission:users.view");
        Route::get("/disabled", [UserController::class, "indexDisabled"])->middleware("permission:users.view");
        Route::get("/search/{name}", [UserController::class, "search"])->middleware("permission:users.view");
        Route::get("/trashed/{id}", [UserController::class, "showTrashed"])->middleware("permission:users.view");
        Route::get("/{id}", [UserController::class, "show"])->middleware("permission:users.view");
        Route::post("/", [UserController::class, "store"])->middleware("permission:users.create");
        Route::put("/{id}", [UserController::class, "update"])->middleware("permission:users.edit");
        Route::delete("/{id}", [UserController::class, "destroy"])->middleware("permission:users.deactivate");
        Route::post("/{id}/restore", [UserController::class, "restore"])->middleware("permission:users.restore");
        Route::post("/{id}/permissions", [UserController::class, "syncPermissions"])->middleware(
            "permission:users.edit",
        );
    });

    Route::prefix("roles")->group(function () {
        Route::get("/", [RoleController::class, "index"])->middleware("permission:roles.view");
        Route::get("/trashed", [RoleController::class, "trashed"])->middleware("permission:roles.view");
        Route::get("/{id}", [RoleController::class, "show"])->middleware("permission:roles.view");
        Route::post("/", [RoleController::class, "store"])->middleware("permission:roles.create");
        Route::put("/{id}", [RoleController::class, "update"])->middleware("permission:roles.edit");
        Route::delete("/{id}", [RoleController::class, "destroy"])->middleware("permission:roles.delete");
        Route::post("/{id}/restore", [RoleController::class, "restore"])->middleware("permission:roles.restore");
    });

    Route::prefix("permissions")->group(function () {
        Route::get("/", [PermissionController::class, "index"])->middleware("permission:permissions.view");
        Route::get("/trashed", [PermissionController::class, "trashed"])->middleware("permission:permissions.view");
        Route::get("/{id}", [PermissionController::class, "show"])->middleware("permission:permissions.view");
        Route::post("/", [PermissionController::class, "store"])->middleware("permission:permissions.create");
        Route::put("/{id}", [PermissionController::class, "update"])->middleware("permission:permissions.edit");
        Route::delete("/{id}", [PermissionController::class, "destroy"])->middleware("permission:permissions.delete");
        Route::post("/{id}/restore", [PermissionController::class, "restore"])->middleware(
            "permission:permissions.restore",
        );
    });

    Route::prefix("users/{userId}/schedules")->group(function () {
        Route::get("/", [UserScheduleController::class, "index"])->middleware("permission:schedules.view");
        Route::post("/", [UserScheduleController::class, "store"])->middleware("permission:schedules.create");
    });

    Route::prefix("schedules")->group(function () {
        Route::put("/{id}", [UserScheduleController::class, "update"])->middleware("permission:schedules.edit");
        Route::delete("/{id}", [UserScheduleController::class, "destroy"])->middleware("permission:schedules.delete");
    });

    Route::prefix("parameters")->group(function () {
        Route::get("/", [ParameterController::class, "index"])->middleware("permission:parameters.view");
        Route::get("/trashed", [ParameterController::class, "trashed"])->middleware("permission:parameters.view");
        Route::get("/{id}", [ParameterController::class, "show"])->middleware("permission:parameters.view");
        Route::post("/", [ParameterController::class, "store"])->middleware("permission:parameters.create");
        Route::put("/{id}", [ParameterController::class, "update"])->middleware("permission:parameters.edit");
        Route::delete("/{id}", [ParameterController::class, "destroy"])->middleware("permission:parameters.delete");
        Route::post("/{id}/restore", [ParameterController::class, "restore"])->middleware(
            "permission:parameters.restore",
        );
    });

    Route::prefix("products")->group(function () {
        Route::get("/", [ProductController::class, "index"])->middleware("permission:catalogs.view");
        Route::get("/trashed", [ProductController::class, "trashed"])->middleware("permission:catalogs.view");
        Route::get("/{id}", [ProductController::class, "show"])->middleware("permission:catalogs.view");
        Route::post("/", [ProductController::class, "store"])->middleware("permission:catalogs.create");
        Route::put("/{id}", [ProductController::class, "update"])->middleware("permission:catalogs.edit");
        Route::delete("/{id}", [ProductController::class, "destroy"])->middleware("permission:catalogs.delete");
        Route::post("/{id}/restore", [ProductController::class, "restore"])->middleware("permission:catalogs.restore");
    });

    Route::prefix("rates")->group(function () {
        Route::get("/", [RateController::class, "index"])->middleware("permission:catalogs.view");
        Route::get("/trashed", [RateController::class, "trashed"])->middleware("permission:catalogs.view");
        Route::get("/{id}", [RateController::class, "show"])->middleware("permission:catalogs.view");
        Route::post("/", [RateController::class, "store"])->middleware("permission:catalogs.create");
        Route::put("/{id}", [RateController::class, "update"])->middleware("permission:catalogs.edit");
        Route::delete("/{id}", [RateController::class, "destroy"])->middleware("permission:catalogs.delete");
        Route::post("/{id}/restore", [RateController::class, "restore"])->middleware("permission:catalogs.restore");
    });

    Route::prefix("sellers")->group(function () {
        Route::get("/", [SellerController::class, "index"])->middleware("permission:catalogs.view");
        Route::get("/trashed", [SellerController::class, "trashed"])->middleware("permission:catalogs.view");
        Route::get("/{id}", [SellerController::class, "show"])->middleware("permission:catalogs.view");
        Route::post("/", [SellerController::class, "store"])->middleware("permission:catalogs.create");
        Route::put("/{id}", [SellerController::class, "update"])->middleware("permission:catalogs.edit");
        Route::delete("/{id}", [SellerController::class, "destroy"])->middleware("permission:catalogs.delete");
        Route::post("/{id}/restore", [SellerController::class, "restore"])->middleware("permission:catalogs.restore");
    });

    Route::prefix("call-reasons")->group(function () {
        Route::get("/", [CallReasonController::class, "index"])->middleware("permission:catalogs.view");
        Route::get("/trashed", [CallReasonController::class, "trashed"])->middleware("permission:catalogs.view");
        Route::get("/{id}", [CallReasonController::class, "show"])->middleware("permission:catalogs.view");
        Route::post("/", [CallReasonController::class, "store"])->middleware("permission:catalogs.create");
        Route::put("/{id}", [CallReasonController::class, "update"])->middleware("permission:catalogs.edit");
        Route::delete("/{id}", [CallReasonController::class, "destroy"])->middleware("permission:catalogs.delete");
        Route::post("/{id}/restore", [CallReasonController::class, "restore"])->middleware(
            "permission:catalogs.restore",
        );
    });

    Route::prefix("states")->group(function () {
        Route::get("/", [StateController::class, "index"])->middleware("permission:catalogs.view");
        Route::get("/{id}", [StateController::class, "show"])->middleware("permission:catalogs.view");
    });

    Route::prefix("activities")->group(function () {
        Route::get("/", [ActivityController::class, "index"])->middleware("permission:audit.view");
    });

    Route::prefix("login-audits")->group(function () {
        Route::get("/", [LoginAuditController::class, "index"])->middleware("permission:access.view");
    });
});
