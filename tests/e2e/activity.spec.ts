import { expect, test } from '@playwright/test';
import { artisan, signIn } from './helpers';

test.beforeEach(async ({ page }) => {
    await signIn(page);
    await page.goto('/dash/activity');
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

        await page.reload();
        await expect(page.getByTestId('activity-live')).toBeVisible();
        await page.waitForTimeout(1_000);

        await expect(page.getByTestId('activity-row')).toHaveCount(rows);
    });

    test('stops the live feed while a filter is on', async ({ page }) => {
        // The stream pushes everything. Mixing that into a filtered list
        // would misreport what the screen claims to be showing.
        await page.getByTestId('filter-log').click();
        await page.getByRole('option', { name: 'e2e' }).click();

        await expect(page.getByTestId('activity-live')).toBeHidden();
    });

    test('offers no way to write to the log', async ({ page }) => {
        // An audit log that can be edited is not an audit log.
        await expect(page.getByRole('button', { name: /удал|измен/i })).toHaveCount(0);
    });

    // Empty data
    test('shows an empty state when a filter matches nothing', async ({
        page,
    }) => {
        await page.goto('/dash/activity?log=ничего-такого-нет');

        await expect(page.getByTestId('activity-empty')).toBeVisible();
        await expect(page.getByTestId('activity-row')).toHaveCount(0);
    });

    test('survives an entry with no causer and no changes', async ({ page }) => {
        artisan('tinker', '--execute=activity()->useLog("e2e")->log("системное событие");');

        const row = page
            .getByTestId('activity-row')
            .filter({ hasText: 'системное событие' });

        await expect(row).toBeVisible({ timeout: 10_000 });
        await expect(row.getByTestId('activity-change')).toHaveCount(0);
    });
});
