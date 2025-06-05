// @ts-check
import {test, expect} from '@playwright/test';

test.describe('Settings Management', () => {
    test.beforeEach(async ({page}) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'password');
        await page.click('#wp-submit');
    });

    test('settings page loads and saves correctly', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');

        await expect(page.locator('a.nav-tab', {hasText: 'PDF Button'})).toBeVisible();
        await expect(page.locator('a.nav-tab', {hasText: 'PDF Setup'})).toBeVisible();
        await expect(page.locator('a.nav-tab', {hasText: 'PDF Header & Footer'})).toBeVisible();
        await expect(page.locator('a.nav-tab', {hasText: 'PDF CSS'})).toBeVisible();

        await page.fill('input[name="dkpdf_pdfbutton_text"]', 'Download PDF');
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await expect(page.locator('.updated')).toContainText('Settings saved');

        await page.reload();
        await expect(page.locator('input[name="dkpdf_pdfbutton_text"]')).toHaveValue('Download PDF');
    });
});

