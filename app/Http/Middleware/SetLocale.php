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

    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::resolve($request);

        app()->setLocale($locale);

        // Без этого route('home') требовал бы явной передачи локали в каждом
        // вызове — и любой забытый превращался бы в ошибку уже в проде.
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    /**
     * Порядок: адрес → профиль → запасной вариант.
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

        return self::FALLBACK;
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
