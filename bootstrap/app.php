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

            // Every error status, not a list of five. The list was the
            // bug: a status outside it — 405 from a GET on a POST-only
            // route, 400, 413 — fell through untouched. With APP_DEBUG on,
            // which `.env.example` ships, that handed the visitor a stack
            // trace and the vendor paths. With it off it was plain HTML,
            // which is exactly what makes Inertia draw the grey modal
            // rule 11 calls a bug. Both verified.
            if ($status < 400) {
                return $response;
            }

            // Statuses with wording of their own keep it; the rest get a
            // generic page. A missing key would otherwise print
            // `common.errors.405.title` at the reader.
            $key = trans()->has("common.errors.{$status}.title") ? (string) $status : 'generic';

            return inertia('errors/error-page', [
                'status' => $status,
                'title' => trans("common.errors.{$key}.title"),
                'description' => trans("common.errors.{$key}.description"),
                'home' => trans('common.errors.home'),
            ])->toResponse($request)->setStatusCode($status);
        });
    })->create();
