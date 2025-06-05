// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Shortcode Functionality', () => {
    test.beforeEach(async ({page}) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'password');
        await page.click('#wp-submit');
    });

    test('dkpdf-button shortcode works', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.getByRole('checkbox', {name: 'post'}).check();
        await page.getByRole('radio', {name: 'Use shortcode'}).check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post-new.php');
        await page.fill('#title', 'Shortcode Test Post');

        await page.click('#content-html');
        await page.fill('#content', 'Before shortcode [dkpdf-button] After shortcode');
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        const postUrl = await page.locator('.notice-success a').getAttribute('href');
        await page.goto(postUrl);

        await expect(page.locator('.dkpdf-button')).toBeVisible();
        await expect(page.locator('text=Before shortcode')).toBeVisible();
        await expect(page.locator('text=After shortcode')).toBeVisible();
    });
});
