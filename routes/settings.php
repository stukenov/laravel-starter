<?php

use App\Http\Controllers\Settings\LocaleController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\TokenController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/locale', [LocaleController::class, 'edit'])->name('locale.edit');
    Route::patch('settings/locale', [LocaleController::class, 'update'])->name('locale.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/sessions', [SessionController::class, 'index'])->name('sessions.index');

    // RULE(api): a token nobody can create is an API nobody has —
    // AGENTS.md. Creating one asks for the password first: a token is a
    // password that does not expire, and anybody who found an unlocked
    // screen could otherwise walk away with permanent access.
    Route::get('settings/tokens', [TokenController::class, 'index'])->name('tokens.index');
    Route::post('settings/tokens', [TokenController::class, 'store'])
        ->middleware(RequirePassword::class)
        ->name('tokens.store');
    Route::delete('settings/tokens/{token}', [TokenController::class, 'destroy'])->name('tokens.destroy');

    // Password confirmation: logging out everywhere is irreversible and
    // belongs to somebody who knows the password, not to whoever found
    // an unlocked screen.
    Route::delete('settings/sessions', [SessionController::class, 'destroy'])
        ->middleware(RequirePassword::class)
        ->name('sessions.destroy');
});
