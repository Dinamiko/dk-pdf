// @ts-check
import {test, expect} from '@playwright/test';
import {loginAsAdmin} from "./utils";
import {execSync} from "node:child_process";

test.describe('PDF Generation - Core Functionality', () => {
    // test.beforeAll(() => {
    //     execSync('wp-env clean tests', { stdio: 'inherit' });
    // });

    test.beforeEach(async ({page}) => {
        await loginAsAdmin(page);
    });

    test('PDF button generates PDF for posts', async ({page}) => {
        // Set default template and configure post display content
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
        await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
        await page.getByRole('button', { name: 'Save Settings' }).click();

        // Configure what content to display in the PDF
        await page.locator('#post_display_title').check();
        await page.locator('#post_display_content').check();
        await page.getByRole('button', { name: 'Save Settings' }).click();

        // Enable PDF button for posts
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
        // Set default template and configure post display content
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
        await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
        await page.getByRole('button', { name: 'Save Settings' }).click();

        // Configure what content to display in the PDF
        await page.locator('#post_display_title').check();
        await page.locator('#post_display_content').check();
        await page.getByRole('button', { name: 'Save Settings' }).click();

        // Enable PDF button for posts
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
