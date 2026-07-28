/**
 * Exactly what `User::toSharedProps()` sends, and nothing else.
 *
 * It used to carry `created_at`, `updated_at`, an `avatar` nothing
 * produces, and an index signature saying "plus whatever else" — which
 * was true, because the middleware handed the page the whole model. A
 * type that admits to unknown extra keys cannot warn anybody that the
 * extra keys arrived.
 */
export type User = {
    // RULE(db): identifiers are uuid, not numbers. See AGENTS.md
    id: string;
    name: string;
    email: string;
    locale: string;
    timezone: string;
    email_verified_at: string | null;
};

export type Auth = {
    user: User;
    /**
     * Permission names the signed-in user holds.
     *
     * For hiding what would be refused anyway — never for deciding
     * anything. The server enforces each of these through a policy, and
     * whatever the browser holds can be edited.
     */
    abilities: string[];
};
