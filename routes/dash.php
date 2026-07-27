<?php

use App\Http\Controllers\Dash\UserController;
use Illuminate\Support\Facades\Route;

/*
 * ПРАВИЛО(конвенции): маршруты RESTful с одинаковыми именами — AGENTS.md.
 *
 * На этой предсказуемости работает генератор make:feature: он порождает
 * ровно такой блок, а страницы Inertia лежат по пути, повторяющему адрес.
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
