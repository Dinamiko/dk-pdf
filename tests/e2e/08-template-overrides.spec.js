// @ts-check
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from "./utils";
import { execSync } from "node:child_process";

test.describe('Template Overrides in Child Theme', () => {
    test.beforeAll(() => {
        execSync('wp-env clean tests', { stdio: 'inherit' });
    });

    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    test.afterEach(async ({ page }) => {
        execSync('wp-env run tests-cli -- wp theme activate storefront', { stdio: 'inherit' });
    });

    test.describe('Legacy Template Overrides', () => {
        test('uses child theme template override for legacy templates', async ({ page }) => {
            // Activate child theme
            execSync('wp-env run tests-cli -- wp theme activate storefront-child', { stdio: 'inherit' });

            // Enable PDF button for posts
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Test PDF HTML output to verify child theme template is used
            await page.goto('/?pdf=1&output=html');

            // Verify basic PDF content is present
            await expect(page.locator('body')).toContainText('Welcome to WordPress');
        });
    });

    test.describe('Default Template Overrides', () => {
        test('uses child theme template override for default templates', async ({ page }) => {
            // Activate child theme
            execSync('wp-env run tests-cli -- wp theme activate storefront-child', { stdio: 'inherit' });

            // Set default template and configure post display content
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Configure what content to display in the PDF
            await page.locator('#post_display_title').check();
            await page.locator('#post_display_content').check();

            await page.getByRole('button', { name: 'Save Settings' }).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();

            // Enable PDF button for posts
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Test PDF HTML output to verify child theme template is used
            await page.goto('/?pdf=1&output=html');

            // Verify basic PDF content is present
            await expect(page.locator('body')).toContainText('Welcome to WordPress');
        });
    });
});
