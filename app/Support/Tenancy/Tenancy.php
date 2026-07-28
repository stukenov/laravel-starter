<?php

namespace App\Support\Tenancy;

/**
 * Which tenant the current request belongs to.
 *
 * Held here rather than read from `auth()` on demand, and that is the
 * whole design. A global scope that called `auth()->user()` would query
 * the users table to answer a question asked *by* a query on the users
 * table — the scope would apply to its own lookup and the request would
 * recurse until it died.
 *
 * So a middleware sets this once, after authentication, and the scope
 * only ever reads a string that is already in memory. While the user is
 * being loaded nothing is set, the scope is inert, and the lookup that
 * signs somebody in is free to find them.
 */
class Tenancy
{
    private static ?string $current = null;

    private static bool $resolved = false;

    /**
     * RULE(tenancy): off by default, and off means nothing changes.
     * See AGENTS.md.
     */
    public static function enabled(): bool
    {
        return (bool) config('tenancy.enabled', false);
    }

    /**
     * The tenant this request is scoped to, or null for none.
     *
     * Null means two different things and both are correct: nobody is
     * signed in yet, or the person signed in is an operator with no
     * tenant of their own. Neither is scoped, which is why
     * `operators_see_everything` is written down in the config rather
     * than left as a property of the code.
     */
    public static function current(): ?string
    {
        return self::enabled() ? self::$current : null;
    }

    /**
     * Whether the middleware has run for this request.
     *
     * A query made before it runs is deliberately unscoped — signing in
     * is exactly that query. A query made after it, with nothing set, is
     * an operator's.
     */
    public static function resolved(): bool
    {
        return self::$resolved;
    }

    public static function set(?string $tenantId): void
    {
        self::$current = $tenantId;
        self::$resolved = true;
    }

    /**
     * Runs a piece of work as a given tenant, then puts it all back.
     *
     * For a queued job or a console command, which have no request and
     * therefore no middleware. The state is restored even when the work
     * throws, or one failed job would silently rescope every job the
     * worker handled afterwards.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public static function as(?string $tenantId, callable $work): mixed
    {
        $previous = self::$current;
        $wasResolved = self::$resolved;

        self::set($tenantId);

        try {
            return $work();
        } finally {
            self::$current = $previous;
            self::$resolved = $wasResolved;
        }
    }

    /**
     * Forgets everything. Tests call this between cases.
     */
    public static function forget(): void
    {
        self::$current = null;
        self::$resolved = false;
    }
}
