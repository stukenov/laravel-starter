import { expect, test } from '@playwright/test';
import { STATE } from './helpers';

/**
 * RULE(mobile): the base class is the phone — AGENTS.md.
 *
 * The panel was written for a wide screen: rows of fixed-width columns
 * inside `overflow-hidden`, which at 390px does not scroll or wrap — it
 * simply cuts the content off, with no scrollbar and nothing in a log to
 * say so. Exactly the class of bug this project keeps meeting.
 *
 * A viewport is the only honest way to catch it, so the check lives here
 * rather than in a note about opening a browser by hand.
 */
// The viewport spelled out rather than `devices['iPhone 13']`: that
// preset also selects WebKit, and only Chromium is installed here. The
// width is what the test is about anyway.
test.use({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
    deviceScaleFactor: 3,
    storageState: STATE.admin,
});

const PAGES = ['/dash', '/dash/users', '/dash/roles', '/dash/activity', '/dash/health'];

/** Nothing may make the document wider than the screen. */
async function overflow(page: import('@playwright/test').Page) {
    return page.evaluate(() => ({
        document: document.documentElement.scrollWidth,
        window: window.innerWidth,
    }));
}

// ── Correct data ─────────────────────────────────────────────────────

for (const path of PAGES) {
    test(`${path} fits the screen`, async ({ page }) => {
        await page.goto(path, { waitUntil: 'domcontentloaded' });

        const size = await overflow(page);

        // A page wider than the window is a page with a sideways scrollbar
        // and content nobody will find.
        expect(size.document).toBeLessThanOrEqual(size.window + 1);
    });
}

test('the navigation can be opened with a thumb', async ({ page }) => {
    // On a phone this button is the only way into the navigation, and it
    // used to be 28 pixels.
    await page.goto('/dash', { waitUntil: 'domcontentloaded' });

    const trigger = page.locator('[data-sidebar="trigger"]');
    const box = await trigger.boundingBox();

    expect(box?.width ?? 0).toBeGreaterThanOrEqual(44);
    expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);
});

test('a user row is readable without sideways scrolling', async ({ page }) => {
    await page.goto('/dash/users', { waitUntil: 'domcontentloaded' });

    const row = page.getByTestId('users-row').first();
    await expect(row).toBeVisible();

    const box = await row.boundingBox();

    // Stacked rather than squeezed: taller than one line of text.
    expect(box?.height ?? 0).toBeGreaterThan(40);
});

// ── Wrong data ───────────────────────────────────────────────────────

test('the column header steps out of the way on a phone', async ({ page }) => {
    // It labels columns, and at this width there are no columns to label.
    await page.goto('/dash/users', { waitUntil: 'domcontentloaded' });

    await expect(page.getByText('Почта', { exact: true })).toBeHidden();
});

test('no control is too small to hit', async ({ page }) => {
    await page.goto('/dash/users', { waitUntil: 'domcontentloaded' });

    const small: string[] = [];

    for (const button of await page.locator('button:visible').all()) {
        const box = await button.boundingBox();

        if (box && (box.height < 32 || box.width < 32)) {
            small.push(`${Math.round(box.width)}×${Math.round(box.height)}`);
        }
    }

    // 32 rather than 44: shadcn's own sizes sit at 36-40 and rewriting
    // every one of them is a bigger decision than this test. What must
    // not happen is a control at half a thumb.
    expect(small).toEqual([]);
});

// ── Empty data ───────────────────────────────────────────────────────

test('an empty list still fits the screen', async ({ page }) => {
    await page.goto('/dash/users?search=nikogo-takogo-net', {
        waitUntil: 'domcontentloaded',
    });

    await expect(page.getByTestId('users-empty')).toBeVisible();

    const size = await overflow(page);
    expect(size.document).toBeLessThanOrEqual(size.window + 1);
});
