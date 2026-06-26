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
        Route::get("/", [UserController::class, "index"])->middleware("permission:usuarios.ver");
        Route::get("/disabled", [UserController::class, "indexDisabled"])->middleware("permission:usuarios.ver");
        Route::get("/search/{name}", [UserController::class, "search"])->middleware("permission:usuarios.ver");
        Route::get("/trashed/{id}", [UserController::class, "showTrashed"])->middleware("permission:usuarios.ver");
        Route::get("/{id}", [UserController::class, "show"])->middleware("permission:usuarios.ver");
        Route::post("/", [UserController::class, "store"])->middleware("permission:usuarios.crear");
        Route::put("/{id}", [UserController::class, "update"])->middleware("permission:usuarios.editar");
        Route::delete("/{id}", [UserController::class, "destroy"])->middleware("permission:usuarios.desactivar");
        Route::post("/{id}/restore", [UserController::class, "restore"])->middleware("permission:usuarios.restaurar");
        Route::post("/{id}/permissions", [UserController::class, "syncPermissions"])->middleware(
            "permission:usuarios.editar",
        );
    });

    Route::prefix("roles")->group(function () {
        Route::get("/", [RoleController::class, "index"])->middleware("permission:roles.ver");
        Route::get("/trashed", [RoleController::class, "trashed"])->middleware("permission:roles.ver");
        Route::get("/{id}", [RoleController::class, "show"])->middleware("permission:roles.ver");
        Route::post("/", [RoleController::class, "store"])->middleware("permission:roles.crear");
        Route::put("/{id}", [RoleController::class, "update"])->middleware("permission:roles.editar");
        Route::delete("/{id}", [RoleController::class, "destroy"])->middleware("permission:roles.eliminar");
        Route::post("/{id}/restore", [RoleController::class, "restore"])->middleware("permission:roles.restaurar");
    });

    Route::prefix("permissions")->group(function () {
        Route::get("/", [PermissionController::class, "index"])->middleware("permission:permisos.ver");
        Route::get("/trashed", [PermissionController::class, "trashed"])->middleware("permission:permisos.ver");
        Route::get("/{id}", [PermissionController::class, "show"])->middleware("permission:permisos.ver");
        Route::post("/", [PermissionController::class, "store"])->middleware("permission:permisos.crear");
        Route::put("/{id}", [PermissionController::class, "update"])->middleware("permission:permisos.editar");
        Route::delete("/{id}", [PermissionController::class, "destroy"])->middleware("permission:permisos.eliminar");
        Route::post("/{id}/restore", [PermissionController::class, "restore"])->middleware(
            "permission:permisos.restaurar",
        );
    });

    Route::prefix("users/{userId}/schedules")->group(function () {
        Route::get("/", [UserScheduleController::class, "index"])->middleware("permission:horarios.ver");
        Route::post("/", [UserScheduleController::class, "store"])->middleware("permission:horarios.crear");
    });

    Route::prefix("schedules")->group(function () {
        Route::put("/{id}", [UserScheduleController::class, "update"])->middleware("permission:horarios.editar");
        Route::delete("/{id}", [UserScheduleController::class, "destroy"])->middleware("permission:horarios.eliminar");
    });

    Route::prefix("parameters")->group(function () {
        Route::get("/", [ParameterController::class, "index"])->middleware("permission:parametros.ver");
        Route::get("/trashed", [ParameterController::class, "trashed"])->middleware("permission:parametros.ver");
        Route::get("/{id}", [ParameterController::class, "show"])->middleware("permission:parametros.ver");
        Route::post("/", [ParameterController::class, "store"])->middleware("permission:parametros.crear");
        Route::put("/{id}", [ParameterController::class, "update"])->middleware("permission:parametros.editar");
        Route::delete("/{id}", [ParameterController::class, "destroy"])->middleware("permission:parametros.eliminar");
        Route::post("/{id}/restore", [ParameterController::class, "restore"])->middleware(
            "permission:parametros.restaurar",
        );
    });

    Route::prefix("products")->group(function () {
        Route::get("/", [ProductController::class, "index"])->middleware("permission:catalogos.ver");
        Route::get("/trashed", [ProductController::class, "trashed"])->middleware("permission:catalogos.ver");
        Route::get("/{id}", [ProductController::class, "show"])->middleware("permission:catalogos.ver");
        Route::post("/", [ProductController::class, "store"])->middleware("permission:catalogos.crear");
        Route::put("/{id}", [ProductController::class, "update"])->middleware("permission:catalogos.editar");
        Route::delete("/{id}", [ProductController::class, "destroy"])->middleware("permission:catalogos.eliminar");
        Route::post("/{id}/restore", [ProductController::class, "restore"])->middleware(
            "permission:catalogos.restaurar",
        );
    });

    Route::prefix("rates")->group(function () {
        Route::get("/", [RateController::class, "index"])->middleware("permission:catalogos.ver");
        Route::get("/trashed", [RateController::class, "trashed"])->middleware("permission:catalogos.ver");
        Route::get("/{id}", [RateController::class, "show"])->middleware("permission:catalogos.ver");
        Route::post("/", [RateController::class, "store"])->middleware("permission:catalogos.crear");
        Route::put("/{id}", [RateController::class, "update"])->middleware("permission:catalogos.editar");
        Route::delete("/{id}", [RateController::class, "destroy"])->middleware("permission:catalogos.eliminar");
        Route::post("/{id}/restore", [RateController::class, "restore"])->middleware("permission:catalogos.restaurar");
    });

    Route::prefix("sellers")->group(function () {
        Route::get("/", [SellerController::class, "index"])->middleware("permission:catalogos.ver");
        Route::get("/trashed", [SellerController::class, "trashed"])->middleware("permission:catalogos.ver");
        Route::get("/{id}", [SellerController::class, "show"])->middleware("permission:catalogos.ver");
        Route::post("/", [SellerController::class, "store"])->middleware("permission:catalogos.crear");
        Route::put("/{id}", [SellerController::class, "update"])->middleware("permission:catalogos.editar");
        Route::delete("/{id}", [SellerController::class, "destroy"])->middleware("permission:catalogos.eliminar");
        Route::post("/{id}/restore", [SellerController::class, "restore"])->middleware(
            "permission:catalogos.restaurar",
        );
    });

    Route::prefix("call-reasons")->group(function () {
        Route::get("/", [CallReasonController::class, "index"])->middleware("permission:catalogos.ver");
        Route::get("/trashed", [CallReasonController::class, "trashed"])->middleware("permission:catalogos.ver");
        Route::get("/{id}", [CallReasonController::class, "show"])->middleware("permission:catalogos.ver");
        Route::post("/", [CallReasonController::class, "store"])->middleware("permission:catalogos.crear");
        Route::put("/{id}", [CallReasonController::class, "update"])->middleware("permission:catalogos.editar");
        Route::delete("/{id}", [CallReasonController::class, "destroy"])->middleware("permission:catalogos.eliminar");
        Route::post("/{id}/restore", [CallReasonController::class, "restore"])->middleware(
            "permission:catalogos.restaurar",
        );
    });

    Route::prefix("states")->group(function () {
        Route::get("/", [StateController::class, "index"])->middleware("permission:catalogos.ver");
        Route::get("/{id}", [StateController::class, "show"])->middleware("permission:catalogos.ver");
    });

    Route::prefix("activities")->group(function () {
        Route::get("/", [ActivityController::class, "index"])->middleware("permission:auditoria.ver");
    });

    Route::prefix("login-audits")->group(function () {
        Route::get("/", [LoginAuditController::class, "index"])->middleware("permission:accesos.ver");
    });
});
