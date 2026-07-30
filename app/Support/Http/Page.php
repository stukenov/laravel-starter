<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * A page with no controller behind it: Inertia renders the component and
 * nothing else happens.
 *
 * Inertia ships `Route::inertia()` for exactly this, and it is what runs
 * underneath. What it does not ship is a type: a macro is a closure, a
 * closure declares no return type, and so every
 * `Route::inertia(...)->name(...)` reads as a method call on `mixed` —
 * which means nothing checks that the route was named at all, and naming
 * is the whole reason these lines are chained.
 *
 * Narrowed once, here, in the same shape as `target()` on the form
 * requests: the impossible case raises rather than travelling on.
 */
final class Page
{
    public static function at(string $uri, string $component): RouteInstance
    {
        $route = Route::inertia($uri, $component);

        if (! $route instanceof RouteInstance) {
            throw new RuntimeException("Route::inertia() did not return a route for [{$uri}].");
        }

        return $route;
    }
}
