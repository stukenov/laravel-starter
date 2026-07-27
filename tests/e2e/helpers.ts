import { execFileSync } from 'node:child_process';
import type { Page } from '@playwright/test';

export const ADMIN = 'admin@e2e.test';
export const MEMBER = 'member@e2e.test';
export const PASSWORD = 'password';

/** Where the setup project leaves the cookies each spec starts with. */
export const STATE = {
    admin: 'playwright/.auth/admin.json',
    member: 'playwright/.auth/member.json',
};

/**
 * Signs in through the real form.
 *
 * Used by the setup project and by the specs that are about logging in.
 * Everything else starts from a saved session — see `auth.setup.ts` for
 * why that matters.
 *
 * Fields are found by marker, not by label: the cabinet has no locale in
 * its URL, so this page is rendered in whatever language the browser asks
 * for.
 */
export async function signInThroughForm(
    page: Page,
    email = ADMIN,
    password = PASSWORD,
) {
    await page.goto('/login');
    await page.getByTestId('login-email').fill(email);
    await page.getByTestId('login-password').fill(password);
    await page.getByTestId('login-button').click();
    await page.waitForURL('**/dash');
}

/**
 * Runs an artisan command against the end-to-end database.
 *
 * The live feed can only be proven by writing from outside the browser:
 * the point of the test is that a row nobody clicked for shows up.
 */
export function artisan(...args: string[]): string {
    return execFileSync('php', ['artisan', ...args], {
        env: { ...process.env, DB_DATABASE: 'laravel_starter_e2e' },
        encoding: 'utf8',
    });
}

/**
 * Forgets the login throttle.
 *
 * It counts five attempts a minute per email and address, which a spec
 * that signs in repeatedly will exhaust. Clearing the cache is where that
 * counter lives.
 */
export function forgetLoginThrottle(): void {
    artisan('cache:clear');
}
