<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * RULE(api): the same controllers, the same policies — AGENTS.md.
 *
 * Versioned from the first line. A URL is a promise, and `/api/users`
 * gives you nowhere to stand when the shape has to change: every caller
 * breaks at once or nothing ever changes. `/api/v1` costs one path
 * segment now and buys a way out later.
 *
 * Guarded by `auth:sanctum` and by each resource's policy, exactly as the
 * panel is. A permission granted in the panel applies here without
 * anybody wiring it up twice, because it is the same policy object.
 */
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Who the token belongs to. The first call any client makes, and the
    // cheapest way to tell a live token from a revoked one.
    Route::get('me', fn (Request $request) => response()->json([
        'data' => $request->user()->toSharedProps(),
    ]))->name('api.me');

    Route::get('users', [UserController::class, 'index'])->name('api.users.index');
    Route::post('users', [UserController::class, 'store'])->name('api.users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('api.users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('api.users.destroy');

    Route::get('roles', [RoleController::class, 'index'])->name('api.roles.index');

    // Read only, for the same reason the panel is: an audit log that can
    // be written through an API is not an audit log.
    Route::get('activity', [ActivityController::class, 'index'])->name('api.activity.index');

    // Tokens manage themselves, so a client that has one can retire it
    // without a browser.
    Route::get('tokens', [TokenController::class, 'index'])->name('api.tokens.index');
    Route::delete('tokens/{token}', [TokenController::class, 'destroy'])->name('api.tokens.destroy');
});
