<?php

use App\Support\Locale\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * RULE(i18n): hybrid URL scheme — AGENTS.md.
 *
 * Public pages carry a /{locale}/ prefix so each language has its own
 * address and correct hreflang. The cabinet has none: the language comes
 * from the profile there, and a prefix would only break links when it
 * changes.
 */

Route::get('/', fn (Request $request) => redirect('/'.Locales::fromBrowser($request)))
    ->name('root');

Route::prefix('{locale}')
    ->whereIn('locale', Locales::SUPPORTED)
    ->group(function () {
        Route::inertia('/', 'home')->name('home');
        Route::inertia('privacy', 'legal/privacy')->name('legal.privacy');
        Route::inertia('terms', 'legal/terms')->name('legal.terms');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

/*
 * The styleguide is a working tool, not demo content: the only place
 * showing every component at once under the current tokens. Never ships.
 */
if (app()->environment('local')) {
    Route::inertia('dev/styleguide', 'dev/styleguide')->name('dev.styleguide');
}

require __DIR__.'/dash.php';
require __DIR__.'/settings.php';
