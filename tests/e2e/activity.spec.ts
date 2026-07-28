import { expect, test } from '@playwright/test';
import { STATE, artisan } from './helpers';

// Already signed in: the throttle allows five logins a minute and this
// file alone has eight tests. See auth.setup.ts.
test.use({ storageState: STATE.admin });

/**
 * `domcontentloaded`, not the default `load`.
 *
 * This page opens an EventSource during hydration and holds it for the
 * connection's whole life. The navigation therefore never reaches `load`
 * in the sense Playwright waits for, and any second navigation inside a
 * test sits there until the test times out — which is exactly what three
 * of these did until it was tracked down.
 */
const OPEN = { waitUntil: 'domcontentloaded' } as const;

/**
 * Ninety seconds, not the usual thirty.
 *
 * This page holds a connection open for twenty-five seconds by design,
 * and a second navigation inside a test waits for the first stream to let
 * go. Measured: every one of these passes at ninety and three of them
 * time out at thirty.
 *
 * The number is the feature's, not the test's. Shorten LIFETIME_SECONDS
 * in ActivityStreamController and this can come down with it.
 */
test.describe.configure({ timeout: 90_000 });

test.beforeEach(async ({ page }) => {
    await page.goto('/dash/activity', OPEN);
});

test.describe('the live activity feed', () => {
    // Correct data
    test('reports itself connected right away', async ({ page }) => {
        // EventSource does not fire `open` until bytes arrive, so the
        // handler writes a comment immediately. Without it the badge sat
        // dark for five seconds on every connection.
        await expect(page.getByTestId('activity-live')).toBeVisible({
            timeout: 2_000,
        });
    });

    test('shows an entry written while the page is open', async ({ page }) => {
        const before = await page.getByTestId('activity-row').count();

        artisan(
            'tinker',
            '--execute=activity()->useLog("e2e")->log("запись из другого процесса");',
        );

        await expect(page.getByText('запись из другого процесса')).toBeVisible({
            timeout: 10_000,
        });
        await expect(page.getByTestId('activity-row')).toHaveCount(before + 1);
    });

    test('puts the newest entry at the top', async ({ page }) => {
        artisan(
            'tinker',
            '--execute=activity()->useLog("e2e")->log("самая свежая запись");',
        );

        await expect(page.getByTestId('activity-row').first()).toContainText(
            'самая свежая запись',
        );
    });

    // Wrong data
    test('does not repeat what the page already showed', async ({ page }) => {
        // A first connection used to replay the whole log, doubling every
        // row on screen. The count is the only thing that catches it.
        const rows = await page.getByTestId('activity-row').count();

        await page.reload(OPEN);

        // The badge only lights once the stream has said something, so by
        // the time it is visible a replay would already have arrived. No
        // arbitrary sleep needed, and none wanted — a fixed wait is either
        // too short to catch the bug or long enough to slow every run.
        await expect(page.getByTestId('activity-live')).toBeVisible();
        await expect(page.getByTestId('activity-row')).toHaveCount(rows);
    });

    test('stops the live feed while a filter is on', async ({ page }) => {
        // The stream pushes everything. Mixing that into a filtered list
        // would misreport what the screen claims to be showing.
        //
        // Straight to the filtered address rather than through the select:
        // its options are whatever the log happens to contain, which makes
        // the click depend on what earlier tests wrote.
        await page.goto('/dash/activity?log=user', OPEN);

        await expect(page.getByTestId('activity-live')).toBeHidden();
    });

    test('offers no way to write to the log', async ({ page }) => {
        // An audit log that can be edited is not an audit log.
        await expect(
            page.getByRole('button', { name: /удал|измен/i }),
        ).toHaveCount(0);
    });

    // Empty data
    test('shows an empty state when a filter matches nothing', async ({
        page,
    }) => {
        await page.goto('/dash/activity?log=ничего-такого-нет', OPEN);

        await expect(page.getByTestId('activity-empty')).toBeVisible();
        await expect(page.getByTestId('activity-row')).toHaveCount(0);
    });

    test('survives an entry with no causer and no changes', async ({
        page,
    }) => {
        artisan(
            'tinker',
            '--execute=activity()->useLog("e2e")->log("системное событие");',
        );

        const row = page
            .getByTestId('activity-row')
            .filter({ hasText: 'системное событие' });

        await expect(row).toBeVisible({ timeout: 10_000 });
        await expect(row.getByTestId('activity-change')).toHaveCount(0);
    });
});
