<?php

namespace App\Support\Html;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * RULE(html): formatted text is cleaned on the way in, once — AGENTS.md.
 *
 * On write, not on read, and the difference matters. Cleaning on read
 * means every place that displays the text has to remember to do it, and
 * the one that forgets is the one that ships. Cleaning on write means
 * what the database holds is already safe, and every reader of it is safe
 * without knowing this class exists.
 *
 * The cost is that a rule change does not apply retroactively — tightening
 * the allowlist leaves whatever is already stored as it was. That is the
 * trade, and it is the right way round: a stored value that was accepted
 * once will not become dangerous, whereas a forgotten call on read is
 * dangerous immediately.
 *
 * The allowlist is deliberately small. Everything in it is something a
 * person writing an article needs; nothing in it can load, execute or
 * point at anything the CSP would otherwise have to argue with. No
 * `style`, no `class`, no `id` — a rich-text field is content, and content
 * that can style itself can also hide a phishing link inside a heading.
 */
class Sanitizer
{
    private static ?HTMLPurifier $purifier = null;

    /**
     * What a formatting editor is allowed to produce.
     *
     * `a[href]` carries `rel="nofollow noopener"` and `target="_blank"`
     * added below: a link written by one person and clicked by another
     * must not hand the opener over, and `noopener` is the only defence
     * against `window.opener` rewriting the page behind it.
     */
    private const ALLOWED = 'p,br,strong,b,em,i,u,s,'
        .'h2,h3,h4,'
        .'ul,ol,li,'
        .'blockquote,code,pre,'
        .'a[href],'
        .'hr';

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $clean = trim(self::purifier()->purify($html));

        // Empty in, empty out — but also "nothing but empty tags" out.
        // A Tiptap document with no content is `<p></p>`, and storing
        // that makes an empty field look filled to every query that asks
        // whether it is null.
        return in_array(strip_tags($clean), ['', ' '], true) && ! str_contains($clean, '<hr')
            ? null
            : $clean;
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier !== null) {
            return self::$purifier;
        }

        $config = HTMLPurifier_Config::createDefault();

        $config->set('HTML.Allowed', self::ALLOWED);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.AutoParagraph', false);

        // Only these schemes. Without this, `javascript:` is refused but
        // `data:` is not, and a data URL is a script the browser will run
        // if anything ever renders it outside an anchor.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);

        $config->set('HTML.TargetBlank', true);
        $config->set('Attr.AllowedRel', ['nofollow', 'noopener', 'noreferrer']);

        // Written where the application can write. Purifier compiles its
        // definitions to disk and silently degrades if it cannot.
        $cache = storage_path('framework/cache/htmlpurifier');

        if (! is_dir($cache)) {
            mkdir($cache, 0o775, true);
        }

        $config->set('Cache.SerializerPath', $cache);

        return self::$purifier = new HTMLPurifier($config);
    }
}
