// @ts-check
import {test, expect} from '@playwright/test';

test.describe('PDF Generation - Core Functionality', () => {
    test.beforeEach(async ({page}) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'password');
        await page.click('#wp-submit');
    });

    test('PDF button generates PDF for posts', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('radio', {name: 'Download PDF directly'}).check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/?p=1'); // Default "Hello World" post

        const downloadPromise = page.waitForEvent('download');
        await page.click('.dkpdf-button');
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toContain('.pdf');
        expect(download.suggestedFilename()).toContain('Hello world'); // Post title
    });

    test('PDF HTML output contains expected content', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('radio', {name: 'Download PDF directly'}).check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        // Navigate to a post and generate HTML output instead of PDF
        await page.goto('/?p=1&pdf=1&output=html');

        await expect(page.locator('body')).toContainText('Welcome to WordPress');
        await expect(page.locator('.dkpdf-button')).not.toBeVisible();
    });
});
