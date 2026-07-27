import { expect, test } from '@playwright/test';
import { signIn } from './helpers';

test.beforeEach(async ({ page }) => {
    await signIn(page);
    await page.goto('/dash/users');
});

test.describe('the users list', () => {
    // Correct data
    test('shows the accounts that exist', async ({ page }) => {
        await expect(page.getByTestId('users-row')).toHaveCount(2);
        await expect(page.getByText('admin@e2e.test')).toBeVisible();
    });

    test('creates a user and shows it in the list', async ({ page }) => {
        await page.getByTestId('users-add').click();

        await page.getByTestId('user-name').fill('Дәурен Құрманов');
        await page.getByTestId('user-email').fill('dauren@e2e.test');
        await page.getByTestId('user-password').fill('another-password');
        await page.getByTestId('user-save').click();

        // Kazakh letters live in a different Unicode subset than Russian
        // ones, and a font missing it renders boxes. Reading the name back
        // proves the whole chain: form, database, render.
        await expect(page.getByText('Дәурен Құрманов')).toBeVisible();
    });

    test('finds a user by a fragment of the email', async ({ page }) => {
        await page.getByTestId('users-search').fill('dauren@');

        await expect(page.getByTestId('users-row')).toHaveCount(1);
    });

    // Wrong data
    test('refuses an address that is already taken', async ({ page }) => {
        await page.getByTestId('users-add').click();

        await page.getByTestId('user-name').fill('Двойник');
        await page.getByTestId('user-email').fill('admin@e2e.test');
        await page.getByTestId('user-password').fill('another-password');
        await page.getByTestId('user-save').click();

        await expect(page.getByText(/уже|занят/i).first()).toBeVisible();
    });

    test('refuses an address that is not one', async ({ page }) => {
        await page.getByTestId('users-add').click();

        await page.getByTestId('user-name').fill('Кто-то');
        await page.getByTestId('user-email').fill('не-почта');
        await page.getByTestId('user-password').fill('another-password');
        await page.getByTestId('user-save').click();

        await expect(page.getByText(/почт/i).nth(1)).toBeVisible();
    });

    // Empty data
    test('says nothing was found rather than showing an empty table', async ({
        page,
    }) => {
        await page.getByTestId('users-search').fill('такого-точно-нет');

        await expect(page.getByTestId('users-empty')).toBeVisible();
        await expect(page.getByTestId('users-row')).toHaveCount(0);
    });

    test('asks for the fields when the form is submitted empty', async ({
        page,
    }) => {
        await page.getByTestId('users-add').click();
        await page.getByTestId('user-save').click();

        await expect(page.getByText(/обязательн|укажите|введите/i).first()).toBeVisible();
    });
});
