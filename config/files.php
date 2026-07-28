<?php

use App\Models\User;

return [

    /*
     * RULE(files): private disk, served through a policy — AGENTS.md.
     *
     * Never `public`. That disk is a symlink out of `storage` into the
     * web root: anything on it is readable by anybody who knows or
     * guesses the URL, with no session, no policy and no log. A file
     * attached to a record is exactly as private as the record, and the
     * only way to keep that true is to serve it through the same policy
     * the record has.
     *
     * The cost is a controller instead of a static file, which for the
     * sizes a form accepts is a rounding error next to the alternative.
     */
    'disk' => env('FILES_DISK', 'local'),

    /*
     * The largest upload a form will take, in kilobytes.
     *
     * PHP has the final say through `upload_max_filesize` and
     * `post_max_size`, and it says it by discarding the request body —
     * so a limit here that is above PHP's produces a validation error
     * about a missing field rather than an oversized one. Ten megabytes
     * is under the usual 20M default with room to spare.
     */
    'max_kilobytes' => (int) env('FILES_MAX_KILOBYTES', 10240),

    /*
     * What an `image` field accepts.
     *
     * By extension and by the mime type the server detects, which are
     * checked separately: a `.png` that is really a PHP script fails the
     * second even though it passes the first. No `svg` — it is a
     * document that can carry script, and serving one from your own
     * origin is the same as hosting the script.
     */
    'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],

    /*
     * Entities whose files may be downloaded, by URL segment.
     *
     * `make:feature` adds a line here for every entity with a file
     * field. The controller resolves nothing that is not listed: a name
     * taken straight from the URL and turned into a class is how a
     * download route becomes a way to instantiate whatever the caller
     * likes.
     */
    'entities' => [
        'users' => User::class,
        // 'articles' => App\Models\Article::class,
    ],

];
