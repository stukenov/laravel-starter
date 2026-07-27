<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        // SetLocale идёт до HandleInertiaRequests: тот отдаёт переводы
        // общими свойствами и должен знать уже выбранный язык.
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
         * ПРАВИЛО(ошибки): страница, а не модальное окно — AGENTS.md.
         *
         * Модалку рисует клиент Inertia, когда сервер отвечает обычным HTML
         * вместо ожидаемого JSON. Здесь причина устраняется: каждый статус
         * из списка возвращается нормальной страницей Inertia.
         *
         * Появилось окно — значит какой-то статус проскочил мимо списка.
         * Чинить надо список, а не клиент.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            // Проверять здесь заголовок X-Inertia — ошибка: тогда прямой
            // заход по битой ссылке (из письма, из поиска) получал бы
            // минимальную blade-заглушку вместо оформленной страницы,
            // а это как раз самый частый случай.
            if ($request->expectsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();

            // 419 — не ошибка, а истёкшая сессия. Пользователь ничего не
            // сломал, ему просто надо войти заново, и мы ведём его туда.
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
