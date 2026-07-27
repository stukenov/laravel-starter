<?php

use App\Http\Controllers\HealthJsonController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
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

/*
 * Both are served by the app, not shipped as files: the sitemap line
 * needs the real domain, and robots has to keep staging out of the index.
 * A file in public/ would win over these routes, so there must not be one.
 */
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsController::class)->name('robots');

/*
 * Health for a monitor that cannot log in. Registered only when a secret
 * exists: the fallback would be an open endpoint listing which parts of
 * the system are down, which is a gift to anybody looking for a way in.
 */
if (config('health.json_secret')) {
    Route::get('health.json', HealthJsonController::class)->name('health.json');
}

/*
 * The styleguide is a working tool, not demo content: the only place
 * showing every component at once under the current tokens. Never ships.
 */
if (app()->environment('local')) {
    Route::inertia('dev/styleguide', 'dev/styleguide')->name('dev.styleguide');
}

require __DIR__.'/dash.php';
require __DIR__.'/settings.php';
