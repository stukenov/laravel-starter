<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Served by the application, not shipped as a file.
 *
 * The sitemap line needs the real domain, which a static file cannot know.
 * And a staging copy that gets indexed is a genuine problem and an awkward
 * one to undo, so anything that is not production refuses everything.
 *
 * A file at `public/robots.txt` would win over this route, so there must
 * not be one — a test holds that.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $body = app()->isProduction()
            ? "User-agent: *\nDisallow: /dash\nDisallow: /settings\n\nSitemap: ".route('sitemap')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
