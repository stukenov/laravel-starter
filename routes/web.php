<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * ПРАВИЛО(локали): гибридная схема адресов — AGENTS.md.
 *
 * Публичная часть — с префиксом /{locale}/, чтобы у каждого языка был свой
 * адрес и корректные hreflang. Кабинет — без префикса: там язык берётся из
 * профиля, а префикс только ломал бы ссылки при его смене.
 */

Route::get('/', fn (Request $request) => redirect('/'.SetLocale::fromBrowser($request)))
    ->name('root');

Route::prefix('{locale}')
    ->whereIn('locale', SetLocale::SUPPORTED)
    ->group(function () {
        Route::inertia('/', 'home')->name('home');
        Route::inertia('privacy', 'legal/privacy')->name('legal.privacy');
        Route::inertia('terms', 'legal/terms')->name('legal.terms');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
