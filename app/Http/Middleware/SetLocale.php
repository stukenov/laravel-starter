<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * RULE(i18n): three languages, hybrid URLs — AGENTS.md.
 *
 * Public pages live under /{locale}/ for correct hreflang; the cabinet
 * has no prefix and takes the language from the profile. So the source
 * of the locale depends on where the request landed, and the order below
 * matters.
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

        // Without this, route('home') would demand an explicit locale at
        // every call site, and one forgotten call becomes a production error.
        URL::defaults(['locale' => $locale]);

        $response = $next($request);

        // Remember the choice only when it was explicit — that is, when the
        // language came from the URL. Otherwise the cookie would pin down an
        // accidental fallback forever.
        if ($request->route('locale') === $locale) {
            $response->headers->setCookie(cookie()->forever(self::COOKIE, $locale));
        }

        return $response;
    }

    /**
     * Order: URL → profile → cookie → browser → fallback.
     *
     * The cookie exists for pages outside the prefix — login and register.
     * Without it, somebody reading the site in Kazakh clicks "Кіру" and
     * gets a Russian form: they have no profile yet, so there is nothing
     * else to read the language from.
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
     * Reads the language from the browser header — root URL only.
     */
    public static function fromBrowser(Request $request): string
    {
        // Symfony substitutes the app locale when the header is absent,
        // making "the browser said nothing" indistinguishable from "the
        // browser asked for English". So trust a real header only.
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
