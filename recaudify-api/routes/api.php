<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LoginAuditController;
use App\Http\Controllers\Api\ParameterController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
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


    Route::prefix("activities")->group(function () {
        Route::get("/", [ActivityController::class, "index"])->middleware("permission:audit.view");
    });

    Route::prefix("login-audits")->group(function () {
        Route::get("/", [LoginAuditController::class, "index"])->middleware("permission:access.view");
    });
});
