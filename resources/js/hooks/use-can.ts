import { usePage } from '@inertiajs/react';

/**
 * RULE(access): the screen shows what the person may do — AGENTS.md.
 *
 * A convenience over the shared props, not a decision: the server
 * enforces every one of these through a policy, and this only stops the
 * interface from offering what it already knows will be refused.
 *
 * Never guard anything that matters on this alone. Anyone can edit what
 * the browser holds.
 */
export function useCan() {
    const { auth } = usePage().props;
    const abilities = (auth?.abilities as string[] | undefined) ?? [];

    return (ability: string) => abilities.includes(ability);
}
