<?php

declare(strict_types=1);

namespace App\Support\Locale;

use Illuminate\Http\Request;

/**
 * RULE(i18n): the list of languages is domain knowledge, not transport.
 *
 * It used to live on the SetLocale middleware, and everything that needed
 * it — migrations, form requests, factories — had to reach into HTTP to
 * get a constant. ArchitectureTest caught that coupling.
 *
 * See AGENTS.md.
 */
final class Locales
{
    public const SUPPORTED = ['ru', 'kk', 'en'];

    public const FALLBACK = 'ru';

    /** Remembered choice, for pages outside the /{locale}/ prefix. */
    public const COOKIE = 'locale';

    public static function supports(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED, true);
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
}
