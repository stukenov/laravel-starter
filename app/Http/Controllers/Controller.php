<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class Controller
{
    /*
     * Laravel 13 does not include this trait by default. Without it,
     * $this->authorize() fails with "method does not exist" — a 500 where
     * you meant a 403, which reads like a broken policy and sends you
     * looking in the wrong place.
     */
    use AuthorizesRequests;

    /**
     * The signed-in person, as a `User` rather than a maybe-one.
     *
     * Every action that reaches for this sits behind `auth`, so null is
     * not a state the application can be in — but the type says it is,
     * at every call site, and static analysis is right to insist.
     * Narrowed once here, in the same shape as `target()` on the form
     * requests: the impossible case raises instead of being carried
     * around and dereferenced on faith.
     *
     * An authentication failure rather than a 500, because if it ever
     * did happen the honest answer is "sign in", not "the server broke".
     *
     * @throws AuthenticationException
     */
    protected function currentUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }

    /**
     * RULE(api): one controller answers both surfaces — AGENTS.md.
     *
     * The API and the panel run through the same actions, the same
     * policies and the same form requests, so a rule enforced in one is
     * enforced in the other by construction. The only difference is the
     * envelope, and it lives here rather than as a branch in every
     * action: a dozen copies of `if ($request->expectsJson())` is how two
     * surfaces drift apart while both look maintained.
     *
     * `$resource` names the prop an API caller gets. A page hands the
     * component several — a list, the search term, a set of options — and
     * only one of them is the resource.
     *
     * @param  array<string, mixed>  $props
     */
    protected function page(Request $request, string $component, array $props, string $resource): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['data' => $props[$resource]]);
        }

        return Inertia::render($component, $props);
    }

    /**
     * What a write answers with.
     *
     * A browser gets a redirect back to where it was, because Inertia
     * expects one and because a form that answers with JSON leaves the
     * person looking at raw text. An API caller gets the record it just
     * made, which is the one thing it cannot work out for itself — above
     * all the id.
     *
     * @param  array<string, mixed>|null  $record
     */
    protected function saved(Request $request, ?array $record = null, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return $record === null
                ? response()->json(status: 204)
                : response()->json(['data' => $record], $status);
        }

        return back();
    }
}
