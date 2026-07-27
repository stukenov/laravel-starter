import { execFileSync } from 'node:child_process';
import type { Page } from '@playwright/test';

export const ADMIN = 'admin@e2e.test';
export const MEMBER = 'member@e2e.test';
export const PASSWORD = 'password';

/**
 * Signs in through the real form rather than a session cookie. Logging in
 * is the one flow every other spec depends on, so it is worth exercising
 * for real each time.
 */
export async function signIn(page: Page, email = ADMIN) {
    await page.goto('/login');
    await page.getByLabel(/почта/i).fill(email);
    await page.getByLabel(/пароль/i).fill(PASSWORD);
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
