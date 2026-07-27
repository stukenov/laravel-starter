<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * ПРАВИЛО(локали): три языка, гибридные адреса — AGENTS.md.
 *
 * Публичная часть живёт под префиксом /{locale}/ ради корректных hreflang,
 * кабинет — без префикса, язык берётся из профиля. Поэтому источник локали
 * зависит от того, куда пришёл запрос, и порядок здесь важен.
 */
class SetLocale
{
    public const SUPPORTED = ['ru', 'kk', 'en'];

    public const FALLBACK = 'ru';

    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::resolve($request);

        app()->setLocale($locale);

        // Без этого route('home') требовал бы явной передачи локали в каждом
        // вызове — и любой забытый превращался бы в ошибку уже в проде.
        URL::defaults(['locale' => $locale]);

        $response = $next($request);

        // Запоминаем выбор ровно тогда, когда он сделан явно — то есть когда
        // язык пришёл из адреса. Иначе кука закрепила бы случайно выбранный
        // запасной вариант навсегда.
        if ($request->route('locale') === $locale) {
            $response->headers->setCookie(cookie()->forever(self::COOKIE, $locale));
        }

        return $response;
    }

    /**
     * Порядок источников: адрес → профиль → кука → браузер → запасной.
     *
     * Кука нужна страницам вне префикса — входу и регистрации. Без неё
     * человек, читавший сайт по-казахски, нажимает «Кіру» и получает
     * русскую форму: профиля у него ещё нет, язык брать неоткуда.
     */
    public static function resolve(Request $request): string
    {
        $fromPath = $request->route('locale');

        if (is_string($fromPath) && self::supports($fromPath)) {
            return $fromPath;
        }

        $userLocale = $request->user()?->locale;

        if (is_string($userLocale) && self::supports($userLocale)) {
            return $userLocale;
        }

        $fromCookie = $request->cookie(self::COOKIE);

        if (is_string($fromCookie) && self::supports($fromCookie)) {
            return $fromCookie;
        }

        return self::fromBrowser($request);
    }

    /**
     * Определяет язык по заголовку браузера — только для корневого адреса.
     */
    public static function fromBrowser(Request $request): string
    {
        // Symfony подставляет локаль приложения, когда заголовка нет вовсе,
        // из-за чего «браузер промолчал» неотличимо от «браузер попросил
        // английский». Поэтому доверяем только настоящему заголовку.
        $header = $request->headers->get('Accept-Language');

        if ($header === null || trim($header) === '') {
            return self::FALLBACK;
        }

        foreach ($request->getLanguages() as $language) {
            $short = strtolower(substr($language, 0, 2));

            if (self::supports($short)) {
                return $short;
            }
        }

        return self::FALLBACK;
    }

    public static function supports(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED, true);
    }
}
