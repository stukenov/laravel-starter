<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RULE(tenancy): the tenant is resolved once, after authentication.
 * See AGENTS.md.
 *
 * It has to run after the session is started and the user is resolved,
 * and before anything queries a scoped model — which is why it sits at
 * the end of the `web` group rather than globally. A request that never
 * reaches the web group never touches a scoped model either.
 *
 * Reading `$request->user()` here is safe and is the reason the whole
 * design works: nothing is set yet, so the users lookup that resolves
 * them is unscoped. Everything after this line is scoped.
 */
class SetTenant
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Tenancy::set($request->user()?->tenant_id);

        return $next($request);
    }
}
