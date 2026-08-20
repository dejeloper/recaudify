<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LoginAuditController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\ParameterController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StateController;
use App\Http\Controllers\Api\StateTransitionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserScheduleController;
use Illuminate\Support\Facades\Route;

// Público: un chequeo de uptime debe funcionar sin credenciales, incluso con la base caída.
Route::get("health", [HealthController::class, "index"]);

Route::prefix("auth")->group(function () {
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);
    Route::get("config", [AuthController::class, "config"]);

    Route::post("refresh", [AuthController::class, "refresh"])->middleware("throttle:10,1");

    Route::middleware(["auth:api", "track.session"])->group(function () {
        Route::get("me", [AuthController::class, "me"]);
        Route::post("login/location", [AuthController::class, "loginLocation"]);
        Route::post("logout", [AuthController::class, "logout"]);
        Route::post("change-password", [AuthController::class, "changePassword"]);

        Route::get("sessions", [SessionController::class, "mine"]);
        Route::post("sessions/{id}/revoke", [SessionController::class, "revokeMine"]);
        Route::post("sessions/revoke-all", [SessionController::class, "revokeAllMine"]);
    });
});

Route::middleware(["auth:api", "track.session", "check.schedule", "force.password.change"])->group(function () {
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
        Route::post("/{id}/reset-password", [UserController::class, "resetPassword"])->middleware(
            "permission:users.reset-password",
        );
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

    Route::get("menu", [MenuItemController::class, "mine"]);

    Route::prefix("menu-items")->group(function () {
        Route::get("/", [MenuItemController::class, "index"])->middleware("permission:menu.view");
        Route::get("/trashed", [MenuItemController::class, "trashed"])->middleware("permission:menu.view");
        Route::get("/{id}", [MenuItemController::class, "show"])->middleware("permission:menu.view");
        Route::post("/", [MenuItemController::class, "store"])->middleware("permission:menu.create");
        Route::put("/{id}", [MenuItemController::class, "update"])->middleware("permission:menu.edit");
        Route::delete("/{id}", [MenuItemController::class, "destroy"])->middleware("permission:menu.delete");
        Route::post("/{id}/restore", [MenuItemController::class, "restore"])->middleware("permission:menu.restore");
    });

    Route::prefix("branches")->group(function () {
        Route::get("/", [BranchController::class, "index"])->middleware("permission:branches.view");
        Route::get("/main", [BranchController::class, "main"])->middleware("permission:branches.view");
        Route::get("/trashed", [BranchController::class, "trashed"])->middleware("permission:branches.view");
        Route::get("/{id}", [BranchController::class, "show"])->middleware("permission:branches.view");
        Route::post("/", [BranchController::class, "store"])->middleware("permission:branches.create");
        Route::put("/{id}", [BranchController::class, "update"])->middleware("permission:branches.edit");
        Route::delete("/{id}", [BranchController::class, "destroy"])->middleware("permission:branches.delete");
        Route::post("/{id}/restore", [BranchController::class, "restore"])->middleware("permission:branches.restore");
    });

    Route::prefix("states")->group(function () {
        Route::get("/", [StateController::class, "index"])->middleware("permission:states.view");
        Route::get("/entities", [StateController::class, "entities"])->middleware("permission:states.view");
        Route::get("/trashed", [StateController::class, "trashed"])->middleware("permission:states.view");
        Route::get("/{id}", [StateController::class, "show"])->middleware("permission:states.view");
        Route::post("/", [StateController::class, "store"])->middleware("permission:states.create");
        Route::put("/{id}", [StateController::class, "update"])->middleware("permission:states.edit");
        Route::delete("/{id}", [StateController::class, "destroy"])->middleware("permission:states.delete");
        Route::post("/{id}/restore", [StateController::class, "restore"])->middleware("permission:states.restore");
    });

    Route::prefix("state-transitions")->group(function () {
        Route::get("/", [StateTransitionController::class, "index"])->middleware("permission:states.view");
        Route::get("/trashed", [StateTransitionController::class, "trashed"])->middleware("permission:states.view");
        Route::get("/{id}", [StateTransitionController::class, "show"])->middleware("permission:states.view");
        Route::post("/", [StateTransitionController::class, "store"])->middleware("permission:states.create");
        Route::put("/{id}", [StateTransitionController::class, "update"])->middleware("permission:states.edit");
        Route::delete("/{id}", [StateTransitionController::class, "destroy"])->middleware("permission:states.delete");
        Route::post("/{id}/restore", [StateTransitionController::class, "restore"])->middleware(
            "permission:states.restore",
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
        Route::get("/purge/preview", [ActivityController::class, "purgePreview"])->middleware("permission:audit.purge");
        Route::post("/purge", [ActivityController::class, "purge"])->middleware("permission:audit.purge");
    });

    Route::prefix("login-audits")->group(function () {
        Route::get("/", [LoginAuditController::class, "index"])->middleware("permission:access.view");
    });

    Route::prefix("sessions")
        ->middleware("role:superadmin")
        ->group(function () {
            Route::get("/", [SessionController::class, "index"])->middleware("permission:sessions.view");
            Route::post("/revoke-all", [SessionController::class, "revokeAllGlobal"])->middleware(
                "permission:sessions.revoke",
            );
            Route::post("/user/{userId}/revoke-all", [SessionController::class, "revokeAllForUser"])->middleware(
                "permission:sessions.revoke",
            );
            Route::post("/{id}/revoke", [SessionController::class, "revoke"])->middleware("permission:sessions.revoke");
        });
});
