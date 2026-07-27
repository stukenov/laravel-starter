import { test as setup } from '@playwright/test';
import { ADMIN, MEMBER, PASSWORD, STATE, signInThroughForm } from './helpers';

/**
 * RULE(auth): five login attempts a minute, per address — AGENTS.md.
 *
 * The limiter itself is in `FortifyServiceProvider::configureRateLimiting`.
 *
 * Which is why the suite does not log in per test. Twenty-five sign-ins
 * from one machine with one email trip the limiter inside the first
 * minute, and every test after that waits for a redirect that is never
 * coming. The limiter is right; signing in twenty-five times was not.
 *
 * So it happens once here, the cookies are saved, and the specs start
 * already signed in. `auth.spec.ts` still logs in for real — that is what
 * it is testing — and clears the limiter between its own tests.
 */
setup('sign in as the administrator', async ({ page }) => {
    await signInThroughForm(page, ADMIN, PASSWORD);
    await page.context().storageState({ path: STATE.admin });
});

setup('sign in as a member', async ({ page }) => {
    await signInThroughForm(page, MEMBER, PASSWORD);
    await page.context().storageState({ path: STATE.member });
});
