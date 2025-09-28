// @ts-check
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from "./utils";
import { execSync } from "node:child_process";

test.describe('Custom Fields Integration', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    test.describe('Custom Fields Tab Visibility', () => {
        test('Custom Fields tab is hidden when legacy template is selected', async ({ page }) => {
            // Navigate to PDF Templates tab
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');

            // Check that Custom Fields tab is not visible
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await expect(page.locator('a.nav-tab', { hasText: 'Custom Fields' })).not.toBeVisible();
        });

        test('Custom Fields tab is visible when default template is selected', async ({ page }) => {
            // Navigate to PDF Templates tab
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');

            // Set default template
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
            await page.getByRole('button', { name: 'Save Settings' }).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();

            // Check that Custom Fields tab is visible
            await expect(page.locator('a.nav-tab', { hasText: 'Custom Fields' })).toBeVisible();
        });
    });

    test.describe('Custom Fields Management', () => {
        test.beforeEach(async () => {
            // Create custom fields on Hello World post (ID: 1) using WP-CLI
            execSync('wp-env run tests-cli -- wp post meta set 1 test_field "Test Value"', { stdio: 'inherit' });
            execSync('wp-env run tests-cli -- wp post meta set 1 product_price "42"', { stdio: 'inherit' });
            execSync('wp-env run tests-cli -- wp post meta set 1 author_name "John Doe"', { stdio: 'inherit' });
        });

        test('Custom Fields section shows for enabled post types', async ({ page }) => {
            // Setup: Ensure default template and post type are configured
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Navigate to Custom Fields tab
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=custom_fields');

            // Verify Post section exists
            await expect(page.locator('label', { hasText: 'Post' })).toBeVisible();
            await expect(page.locator('select[name="dkpdf_custom_fields_post[]"]')).toBeVisible();
        });

        test('Can select and save custom fields', async ({ page }) => {
            // Setup configuration
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Navigate to Custom Fields tab
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=custom_fields');

            // Wait for Select2 to initialize
            await page.waitForSelector('.select2-container');

            // Open dropdown and select fields
            await page.locator('.select2-container').click();
            await page.waitForSelector('.select2-dropdown', { state: 'visible' });

            // Select test_field and product_price
            await page.locator('.select2-results__option', { hasText: 'test_field' }).click();
            await page.locator('.select2-container').click();
            await page.locator('.select2-results__option', { hasText: 'product_price' }).click();

            // Save settings
            await page.getByRole('button', { name: 'Save Settings' }).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();

            // Verify persistence by reloading page
            await page.reload();
            await page.waitForSelector('.select2-container');

            // Check that selected fields are still selected
            await expect(page.locator('.select2-selection__choice', { hasText: 'test_field' })).toBeVisible();
            await expect(page.locator('.select2-selection__choice', { hasText: 'product_price' })).toBeVisible();
        });
    });

    test.describe('Custom Fields in PDF Output', () => {
        test.beforeEach(async ({ page }) => {
            // Create custom fields
            execSync('wp-env run tests-cli -- wp post meta set 1 test_field "Test Value"', { stdio: 'inherit' });
            execSync('wp-env run tests-cli -- wp post meta set 1 product_price "42"', { stdio: 'inherit' });

            // Setup configuration
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();
        });

        test('Selected custom fields appear in PDF HTML output', async ({ page }) => {
            // Configure custom fields selection
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=custom_fields');
            await page.waitForSelector('.select2-container');

            // Select custom fields
            await page.locator('.select2-container').click();
            await page.waitForSelector('.select2-dropdown', { state: 'visible' });
            await page.locator('.select2-results__option', { hasText: 'test_field' }).click();
            await page.locator('.select2-container').click();
            await page.locator('.select2-results__option', { hasText: 'product_price' }).click();

            await page.getByRole('button', { name: 'Save Settings' }).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();

            // Navigate to PDF HTML output (admin login required)
            await page.goto('/hello-world/?pdf=1&output=html');

            // Verify custom fields appear in output
            await expect(page.locator('body')).toContainText('Test Field: Test Value');
            await expect(page.locator('body')).toContainText('Product Price: 42');
        });
    });
});
