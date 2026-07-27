<?php

namespace App\Support\Spam;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Honeypot\SpamResponder\SpamResponder;

/**
 * RULE(errors): pages, not modals — AGENTS.md.
 *
 * Spatie answers spam with a blank 200 page. Inertia cannot read that as
 * a response it understands and draws its error modal instead, which is
 * exactly what this project does not do.
 *
 * So the request goes back where it came from, with nothing said. A bot
 * learns nothing about why it failed, and a person who somehow triggered
 * it — an autofill extension filling a field it cannot see, a form left
 * open — simply sees the form again.
 *
 * It is logged, because a honeypot that fires on real people is a bug and
 * nobody would ever find out otherwise.
 */
class SilentRedirectResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next): RedirectResponse
    {
        Log::info('Honeypot rejected a submission.', [
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return back();
    }
}
