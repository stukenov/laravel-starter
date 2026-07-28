<?php

use App\Http\Controllers\Dash\ActivityController;
use App\Http\Controllers\Dash\ActivityStreamController;
use App\Http\Controllers\Dash\FileController;
use App\Http\Controllers\Dash\HealthController;
use App\Http\Controllers\Dash\RoleController;
use App\Http\Controllers\Dash\UserController;
use App\Support\Access\Ability;
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
        // The panel home lives at the panel's own root. It kept the URL
        // /dashboard from the upstream starter for a while, which left the
        // one page nobody could reach by trimming the address bar.
        Route::inertia('/', 'dashboard')->name('home');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        // The permission matrix is a large form, so editing gets its own
        // page rather than a dialog. See AGENTS.md.
        Route::get('roles/{role}', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // RULE(files): as private as the record they belong to —
        // AGENTS.md. One route, resolved only against the entities named
        // in config/files.php, authorised by the record's own policy.
        Route::get('files/{entity}/{id}/{field}', FileController::class)->name('files.show');

        // Read only on purpose: an audit log that can be edited is not one.
        Route::get('activity', [ActivityController::class, 'index'])->name('activity.index');
        Route::get('activity/stream', ActivityStreamController::class)->name('activity.stream');

        // No model, so no policy — the permission is a gate of its own,
        // registered by spatie under the same name.
        Route::get('health', [HealthController::class, 'index'])
            ->middleware('can:'.Ability::VIEW_HEALTH)
            ->name('health.index');
    });
