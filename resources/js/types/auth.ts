export type User = {
    // RULE(db): identifiers are uuid, not numbers. See AGENTS.md
    id: string;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
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
