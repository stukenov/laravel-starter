<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Csp\AddCspHeaders;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state', 'locale']);

        /*
         * RULE(security): who is allowed to say where a request came from.
         *
         * With the deployment in README — nginx talking to PHP-FPM on the
         * same host — nothing is needed here: nginx passes the real client
         * address as REMOTE_ADDR and sets HTTPS itself, so `$request->ip()`
         * and `$request->secure()` are already right.
         *
         * Put a CDN or a load balancer in front and both become wrong at
         * once, silently. Every visitor then shares one address, so the
         * login limiter ("five a minute per email and address") turns into
         * one bucket for the whole internet; the sessions page shows that
         * same address for everybody; and HSTS disappears, because
         * SecurityHeaders only sends it when the request looks secure.
         *
         * So it is env-driven and empty by default: set TRUSTED_PROXIES to
         * the balancer's addresses, or to `*` when they are not knowable
         * and the app is unreachable except through it. Trusting `*` on an
         * app that can be reached directly lets anyone forge their own IP.
         */
        $proxies = array_values(array_filter(
            array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
        ));

        if ($proxies !== []) {
            $middleware->trustProxies(
                at: $proxies === ['*'] ? '*' : $proxies,
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        // SetLocale runs before HandleInertiaRequests: that one ships the
        // translations as shared props and needs the language decided.
        // Security headers go global, not into the web group: an unknown
        // route would otherwise answer without any of them, because the
        // web group never runs when no route matches.
        $middleware->append([
            SecurityHeaders::class,
            AddCspHeaders::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * RULE(errors): a page, not a modal — AGENTS.md.
         *
         * Inertia draws the modal when the server answers with plain HTML
         * instead of the expected JSON. This removes the cause: every
         * status in the list comes back as a proper Inertia page.
         *
         * If a modal shows up, a status slipped past the list. Fix the
         * list, not the client.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // Checking X-Inertia here is a mistake: a direct hit on a
            // broken link — from an email, from search — would then get
            // the bare blade fallback instead of a designed page, and
            // that is the most common case of all.
            if ($request->expectsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();

            // 419 is not an error but an expired session. Nothing is
            // broken; the user simply needs to log in again, so take
            // them there.
            if ($status === 419) {
                return redirect()
                    ->guest(route('login'))
                    ->with('status', trans('common.auth.session_expired'));
            }

            if (! in_array($status, [403, 404, 429, 500, 503], true)) {
                return $response;
            }

            return inertia('errors/error-page', [
                'status' => $status,
                'title' => trans("common.errors.{$status}.title"),
                'description' => trans("common.errors.{$status}.description"),
                'home' => trans('common.errors.home'),
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
