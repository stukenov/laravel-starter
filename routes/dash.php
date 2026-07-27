<?php

use App\Http\Controllers\Dash\UserController;
use Illuminate\Support\Facades\Route;

/*
 * RULE(conventions): RESTful routes with identical names — AGENTS.md.
 *
 * make:feature relies on this predictability: it emits exactly this block,
 * and the Inertia pages sit at a path mirroring the URL.
 */
Route::middleware(['auth', 'verified'])
    ->prefix('dash')
    ->name('dash.')
    ->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
