// @ts-check
import { test, expect } from '@playwright/test';

test('PDF button generates a PDF', async ({ page }) => {
    // Login to WordPress admin
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');

    // Configure settings page
    await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
    await page.getByRole('checkbox', { name: 'post' }).check();
    await page.getByRole('radio', { name: 'Download PDF directly' }).check();
    await page.getByRole('button', { name: 'Save Settings' }).click();

    // Generate PDF
    await page.goto('/?p=1');
    const downloadPromise = page.waitForEvent('download');
    await page.click('.dkpdf-button');
    const download = await downloadPromise;

    // Verify download started
    expect(download.suggestedFilename()).toContain('.pdf');
});
