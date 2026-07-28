<?php

use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

/**
 * RULE(security): a honeypot instead of a captcha — AGENTS.md.
 *
 * Which routes carry the honeypot used to be one hard-coded string in a
 * provider. That was right for a starter with exactly one public form and
 * wrong the moment a project added a second: the new form would be
 * unprotected, nothing would say so, and a spammer would find it before
 * anybody else did.
 *
 * The list lives in `config/security.php` now, and this checks that every
 * name in it reaches a route that exists and actually carries the
 * middleware. A route name with a typo protects nothing — the same shape
 * of defect as a misspelled permission, and just as quiet.
 */
function routeMiddleware(string $name): array
{
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->getName() === $name);

    return $route ? $route->gatherMiddleware() : [];
}

// ── Correct data ─────────────────────────────────────────────────────

it('protects every route the config names', function () {
    $unprotected = collect(config('security.spam_protected_routes'))
        ->reject(fn (string $name) => in_array(ProtectAgainstSpam::class, routeMiddleware($name), true))
        ->values();

    expect($unprotected->all())->toBe([]);
});

it('names routes that exist', function () {
    // A name that matches nothing is silently a no-op: the loop finds no
    // route, adds no middleware, and reports success.
    $missing = collect(config('security.spam_protected_routes'))
        ->reject(fn (string $name) => Route::has($name))
        ->values();

    expect($missing->all())->toBe([]);
});

it('has something in the list', function () {
    expect(config('security.spam_protected_routes'))->not->toBeEmpty();
});

// ── Wrong data ───────────────────────────────────────────────────────

it('leaves sign-in to the rate limiter instead', function () {
    // A honeypot field on a login form fights password managers and buys
    // nothing. What guards that route is five attempts a minute per
    // email and address.
    expect(routeMiddleware('login.store'))
        ->not->toContain(ProtectAgainstSpam::class);
});

it('protects nothing that is not on the list', function () {
    $protected = (array) config('security.spam_protected_routes');

    $unexpected = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array(ProtectAgainstSpam::class, $route->gatherMiddleware(), true))
        ->map(fn ($route) => $route->getName())
        ->reject(fn (?string $name) => in_array($name, $protected, true))
        ->values();

    expect($unexpected->all())->toBe([]);
});

// ── Empty data ───────────────────────────────────────────────────────

it('reads middleware off a route at all', function () {
    // Every assertion above passes if `gatherMiddleware()` always returns
    // an empty array.
    expect(routeMiddleware('register.store'))->not->toBeEmpty();
});
