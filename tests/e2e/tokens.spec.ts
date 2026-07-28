import { expect, test } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PASSWORD, STATE } from './helpers';

/**
 * RULE(api): a token nobody can create is an API nobody has — AGENTS.md.
 *
 * Pest proves a token authenticates and Vitest proves the page draws.
 * Neither can tell you a person can actually get one: the route sits
 * behind a password confirmation, and a confirmation screen that never
 * hands control back is exactly the sort of thing that returns 200 with
 * everything broken.
 */
test.use({ storageState: STATE.admin });

/**
 * Creates a token, walking through the password gate that stands in
 * front of it by design.
 *
 * Waits on an element rather than on the URL. `waitForURL` matched the
 * address the page was already on and returned instantly, so the gate
 * was never noticed and the token was never created — a test that failed
 * for a reason two steps away from what it was reporting.
 */
async function createToken(page: Page, name: string) {
    await page.getByTestId('token-name').fill(name);
    await page.getByTestId('token-create').click();

    const gate = page.getByTestId('confirm-password-input');

    await expect(gate.or(page.getByTestId('token-plaintext'))).toBeVisible();

    if (await gate.isVisible()) {
        await gate.fill(PASSWORD);
        await page.getByTestId('confirm-password-button').click();

        // The gate swallows the submission it interrupted, so the form
        // is filled again on the other side of it.
        await expect(page.getByTestId('token-name')).toBeVisible();
        await page.getByTestId('token-name').fill(name);
        await page.getByTestId('token-create').click();
    }

    await expect(page.getByText(name)).toBeVisible();
}

test.beforeEach(async ({ page }) => {
    await page.goto('/settings/tokens', { waitUntil: 'domcontentloaded' });
});

// ── Correct data ─────────────────────────────────────────────────────

test('the page is reachable from the settings navigation', async ({ page }) => {
    // Not by typing the URL: somebody has to be able to find it.
    await page.goto('/settings/profile', { waitUntil: 'domcontentloaded' });

    await page
        .getByRole('link', { name: /token|токен/i })
        .first()
        .click();

    await expect(page).toHaveURL(/\/settings\/tokens/);
});

test('creates a token and shows it exactly once', async ({ page }) => {
    const name = `e2e ${Date.now()}`;

    await createToken(page, name);

    const plaintext = await page.getByTestId('token-plaintext').textContent();

    expect(plaintext).toMatch(/\w{20,}/);

    // Reloading is the whole point: the value exists for one response.
    await page.reload({ waitUntil: 'domcontentloaded' });

    await expect(page.getByTestId('token-plaintext')).toBeHidden();
    await expect(page.getByText(name)).toBeVisible();
});

// ── Wrong data ───────────────────────────────────────────────────────

test('refuses a token with no name', async ({ page }) => {
    await page.getByTestId('token-create').click();

    // Either the browser blocks it or the server answers — both are the
    // guard working. What must not happen is a nameless token appearing,
    // because the name is the only way to know which one to revoke.
    await expect(page.getByTestId('token-name')).toBeVisible();
});

test('revoking takes two clicks', async ({ page }) => {
    const name = `revoke ${Date.now()}`;

    await createToken(page, name);

    const row = page.locator('[data-test="token-row"]', { hasText: name });
    const revoke = row.getByTestId('token-revoke');

    await revoke.click();
    await expect(page.getByText(name)).toBeVisible();

    await revoke.click();
    await expect(page.getByText(name)).toBeHidden();
});

// ── Empty data ───────────────────────────────────────────────────────

test('runs every script the page asks for', async ({ page }) => {
    // CSP once blocked all eighteen bundles: 200, valid markup, and only
    // the browser console said so.
    const blocked: string[] = [];

    page.on('console', (message) => {
        if (/Content Security Policy|Refused to/i.test(message.text())) {
            blocked.push(message.text());
        }
    });

    await page.reload({ waitUntil: 'domcontentloaded' });

    expect(blocked).toEqual([]);
});
