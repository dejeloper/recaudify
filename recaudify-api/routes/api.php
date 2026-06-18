<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1');

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:api')->group(function () {

    Route::prefix('users')->group(function () {
        Route::get('/',                  [UserController::class, 'index'])->middleware('permission:usuarios.ver');
        Route::get('/disabled',          [UserController::class, 'indexDisabled'])->middleware('permission:usuarios.ver');
        Route::get('/search/{name}',     [UserController::class, 'search'])->middleware('permission:usuarios.ver');
        Route::get('/trashed/{id}',      [UserController::class, 'showTrashed'])->middleware('permission:usuarios.ver');
        Route::get('/{id}',              [UserController::class, 'show'])->middleware('permission:usuarios.ver');
        Route::post('/',                 [UserController::class, 'store'])->middleware('permission:usuarios.crear');
        Route::put('/{id}',              [UserController::class, 'update'])->middleware('permission:usuarios.editar');
        Route::delete('/{id}',           [UserController::class, 'destroy'])->middleware('permission:usuarios.desactivar');
        Route::post('/{id}/restore',     [UserController::class, 'restore'])->middleware('permission:usuarios.restaurar');
        Route::post('/{id}/permissions', [UserController::class, 'syncPermissions'])->middleware('permission:usuarios.editar');
    });

    Route::prefix('roles')->middleware('role:administrador')->group(function () {
        Route::get('/',      [RoleController::class, 'index']);
        Route::get('/{id}',  [RoleController::class, 'show']);
        Route::post('/',     [RoleController::class, 'store']);
        Route::put('/{id}',  [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
    });

    Route::prefix('permissions')->middleware('role:administrador')->group(function () {
        Route::get('/',      [PermissionController::class, 'index']);
        Route::get('/{id}',  [PermissionController::class, 'show']);
        Route::post('/',     [PermissionController::class, 'store']);
        Route::put('/{id}',  [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
    });

});
