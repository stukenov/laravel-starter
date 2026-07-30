<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locale\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * RULE(i18n): three languages, hybrid URLs — AGENTS.md.
 *
 * Public pages live under /{locale}/ for correct hreflang; the cabinet has
 * no prefix and takes the language from the profile. So the source of the
 * locale depends on where the request landed, and the order below matters.
 *
 * The list of languages itself lives in App\Support\Locale\Locales —
 * everything else needs it too, and reaching into HTTP for a constant is
 * the coupling ArchitectureTest exists to prevent.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
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
            $response->headers->setCookie(cookie()->forever(Locales::COOKIE, $locale));
        }

        return $response;
    }

    /**
     * Order: URL → profile → cookie → browser → fallback.
     *
     * The cookie exists for pages outside the prefix — login and register.
     * Without it, somebody reading the site in Kazakh clicks through and
     * gets a Russian form: they have no profile yet, so there is nothing
     * else to read the language from.
     */
    public static function resolve(Request $request): string
    {
        $fromPath = $request->route('locale');

        if (is_string($fromPath) && Locales::supports($fromPath)) {
            return $fromPath;
        }

        $userLocale = $request->user()?->locale;

        if (is_string($userLocale) && Locales::supports($userLocale)) {
            return $userLocale;
        }

        $fromCookie = $request->cookie(Locales::COOKIE);

        if (is_string($fromCookie) && Locales::supports($fromCookie)) {
            return $fromCookie;
        }

        return Locales::fromBrowser($request);
    }
}
