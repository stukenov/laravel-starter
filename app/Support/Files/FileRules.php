<?php

declare(strict_types=1);

namespace App\Support\Files;

use Illuminate\Support\Facades\Config;

/**
 * RULE(files): what a form accepts, said once — AGENTS.md.
 *
 * Every form with a file column wants the same rules built out of the
 * same three values in `config/files.php`, and `config()` answers
 * `mixed` — so each of them had to narrow the same three values before
 * it could concatenate them into a rule. Written out per form that was
 * three lines nobody reads and one place per entity to get wrong.
 *
 * `make:feature` emits calls to these rather than the expressions
 * themselves, so a project that changes what an upload may be changes it
 * here and every form follows.
 */
final class FileRules
{
    /**
     * Two checks, not one: `mimes` reads the extension and `image` reads
     * what the file actually is. A `.png` that is really a script passes
     * the first and fails the second. No svg — it is a document that can
     * carry script, and serving one from your own origin is the same as
     * hosting the script.
     *
     * @return array<int, string>
     */
    public static function image(): array
    {
        return [
            'nullable',
            'image',
            'mimes:'.implode(',', array_filter(Config::array('files.image_mimes'), is_string(...))),
            'max:'.Config::integer('files.max_kilobytes'),
        ];
    }

    /**
     * Anything, up to the size limit. PHP has the final say through
     * `upload_max_filesize`, and it says it by discarding the request
     * body — see the note on the limit in `config/files.php`.
     *
     * @return array<int, string>
     */
    public static function any(): array
    {
        return [
            'nullable',
            'file',
            'max:'.Config::integer('files.max_kilobytes'),
        ];
    }
}
