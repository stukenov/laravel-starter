import { expect, test } from '@playwright/test';
import { ADMIN, MEMBER, PASSWORD, signIn } from './helpers';

test.describe('signing in', () => {
    // Correct data
    test('lands in the panel', async ({ page }) => {
        await signIn(page);

        await expect(page).toHaveURL(/\/dash$/);
        await expect(page.getByRole('link', { name: 'Пользователи' })).toBeVisible();
    });

    test('runs every script the page asks for', async ({ page }) => {
        // The whole reason this level exists. CSP once blocked all 18
        // bundles: the response was 200, the markup was valid, and only a
        // browser console said anything.
        const blocked: string[] = [];

        page.on('console', (message) => {
            if (/Content Security Policy|Refused to/i.test(message.text())) {
                blocked.push(message.text());
            }
        });

        await signIn(page);

        expect(blocked).toEqual([]);
    });

    test('renders the same markup on the server and the client', async ({
        page,
    }) => {
        // A hydration mismatch means the SSR bundle and the browser
        // disagree — a stale bundle, or a value computed differently in
        // Node. React reports it and nothing else does.
        const mismatches: string[] = [];

        page.on('pageerror', (error) => {
            if (/Minified React error #41[89]|hydrat/i.test(error.message)) {
                mismatches.push(error.message);
            }
        });

        await signIn(page);
        await page.goto('/dash/users');

        expect(mismatches).toEqual([]);
    });

    // Wrong data
    test('refuses a wrong password and says so', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel(/почта/i).fill(ADMIN);
        await page.getByLabel(/пароль/i).fill('не тот пароль');
        await page.getByTestId('login-button').click();

        await expect(page).toHaveURL(/\/login/);
        await expect(page.getByText(/неверн|не совпад|не найден/i).first()).toBeVisible();
    });

    test('keeps a member out of the panel sections', async ({ page }) => {
        await signIn(page, MEMBER);
        await page.goto('/dash/users');

        await expect(page.getByText('403')).toBeVisible();
    });

    // Empty data
    test('asks for the fields when the form is empty', async ({ page }) => {
        await page.goto('/login');
        await page.getByTestId('login-button').click();

        await expect(page).toHaveURL(/\/login/);
        await expect(page.getByText(/обязательн|укажите|введите/i).first()).toBeVisible();
    });
});

test.describe('signing out', () => {
    test('returns to a public page and locks the panel', async ({ page }) => {
        await signIn(page);

        // The panel is gone once the session is: this is the check that
        // matters, and it does not depend on where the menu item sits.
        await page.request.post('/logout', {
            headers: { 'X-XSRF-TOKEN': await xsrf(page) },
        });

        await page.goto('/dash/users');
        await expect(page).toHaveURL(/\/login/);
    });
});

async function xsrf(page: import('@playwright/test').Page): Promise<string> {
    const cookies = await page.context().cookies();
    const token = cookies.find((cookie) => cookie.name === 'XSRF-TOKEN');

    return decodeURIComponent(token?.value ?? '');
}

test.describe('credentials', () => {
    test('does not accept the password of another account', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel(/почта/i).fill(MEMBER);
        await page.getByLabel(/пароль/i).fill(`${PASSWORD}-wrong`);
        await page.getByTestId('login-button').click();

        await expect(page).toHaveURL(/\/login/);
    });
});
