// @ts-check
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from "./utils";

test.describe('Legacy Template Functionality', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);

        // Ensure legacy template is selected (default)
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
        await page.selectOption('select[name="dkpdf_selected_template"]', 'legacy/');
        await page.getByRole('button', { name: 'Save Settings' }).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();
    });

    test.describe('PDF Generation', () => {
        test('generates PDF for posts', async ({ page }) => {
            // Enable PDF button for posts
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('radio', { name: 'Download PDF directly' }).check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Navigate to default post and test PDF generation
            await page.goto('/?p=1'); // Default "Hello World" post

            const downloadPromise = page.waitForEvent('download');
            await page.click('.dkpdf-button');
            const download = await downloadPromise;

            expect(download.suggestedFilename()).toContain('.pdf');
            expect(download.suggestedFilename()).toContain('Hello world');
        });

        test('generates PDF for pages', async ({ page }) => {
            // Enable PDF button for pages
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_page').check();
            await page.getByRole('radio', { name: 'Download PDF directly' }).check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Navigate to default "Sample Page" (ID 2) and test PDF generation
            await page.goto('/?page_id=2');

            const downloadPromise = page.waitForEvent('download');
            await page.click('.dkpdf-button');
            const download = await downloadPromise;

            expect(download.suggestedFilename()).toContain('.pdf');
            expect(download.suggestedFilename()).toContain('Sample Page');
        });

        test('PDF HTML output contains expected content', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('radio', { name: 'Download PDF directly' }).check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Navigate to default post and generate HTML output instead of PDF
            await page.goto('/?pdf=1&output=html');

            await expect(page.locator('body')).toContainText('Welcome to WordPress');
            await expect(page.locator('.dkpdf-button')).not.toBeVisible();
        });
    });

    test.describe('Button Display', () => {
        test('button appears on single post', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/?p=1');
            await expect(page.locator('.dkpdf-button')).toBeVisible();
        });

        test('button appears on single page', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_page').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Navigate to default "Sample Page" (ID 2)
            await page.goto('/?page_id=2');
            await expect(page.locator('.dkpdf-button')).toBeVisible();
        });

        test('button does not appear when post type is disabled', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').uncheck();
            await page.locator('#pdfbutton_post_types_page').uncheck();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/?p=1');
            await expect(page.locator('.dkpdf-button')).not.toBeVisible();
        });

        test('button position settings work correctly', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();

            // Test before content position
            await page.getByRole('radio', { name: 'Before content' }).check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/?p=1');
            const buttonElement = page.locator('.dkpdf-button-container');
            await expect(buttonElement).toBeVisible();

            // Test after content position
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.getByRole('radio', { name: 'After content' }).check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            await page.goto('/?p=1');
            await expect(buttonElement).toBeVisible();
        });
    });

    test.describe('Shortcode Functionality', () => {
        test('dkpdf-button shortcode works', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('radio', { name: 'Use shortcode' }).check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Edit the default "Hello world" post to add shortcode
            await page.goto('/wp-admin/post.php?post=1&action=edit');
            await page.click('#content-html');
            await page.fill('#content', 'Before shortcode [dkpdf-button] After shortcode');
            await page.click('#publish');
            await page.waitForSelector('.notice-success');

            // Test in normal context
            await page.goto('/?p=1');
            await expect(page.locator('.dkpdf-button')).toBeVisible();
            await expect(page.locator('text=Before shortcode')).toBeVisible();
            await expect(page.locator('text=After shortcode')).toBeVisible();

            // Test in PDF HTML output context
            await page.goto('/?pdf=1&output=html');
            await expect(page.locator('body')).toContainText('Before shortcode');
            await expect(page.locator('body')).toContainText('After shortcode');
        });

        test('dkpdf-remove shortcode works', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Edit the default "Hello world" post to add dkpdf-remove shortcode
            await page.goto('/wp-admin/post.php?post=1&action=edit');
            await page.click('#content-html');
            await page.fill('#content', 'Before remove [dkpdf-remove]Content to remove[/dkpdf-remove] After remove');
            await page.click('#publish');
            await page.waitForSelector('.notice-success');

            // Test in normal context - content should be visible
            await page.goto('/?p=1');
            await expect(page.locator('text=Before remove')).toBeVisible();
            await expect(page.locator('text=After remove')).toBeVisible();
            await expect(page.locator('text=Content to remove')).toBeVisible();

            // Test in PDF HTML output context - content should be removed
            await page.goto('/?pdf=1&output=html');
            await expect(page.locator('body')).toContainText('Before remove');
            await expect(page.locator('body')).toContainText('After remove');
            await expect(page.locator('body')).not.toContainText('Content to remove');
        });

        test('dkpdf-pagebreak shortcode works', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Edit the default "Hello world" post to add pagebreak shortcode
            await page.goto('/wp-admin/post.php?post=1&action=edit');
            await page.click('#content-html');
            await page.fill('#content', 'Before pagebreak [dkpdf-pagebreak] After pagebreak');
            await page.click('#publish');
            await page.waitForSelector('.notice-success');

            // Test in normal context - should not have pagebreak markup
            await page.goto('/?p=1');
            await expect(page.locator('text=Before pagebreak')).toBeVisible();
            await expect(page.locator('text=After pagebreak')).toBeVisible();
            const normalContent = await page.locator('.entry-content').innerHTML();
            expect(normalContent).not.toContain('<pagebreak>');

            // Test in PDF HTML output context - should have pagebreak markup
            await page.goto('/?pdf=1&output=html');
            await expect(page.locator('body')).toContainText('Before pagebreak');
            await expect(page.locator('body')).toContainText('After pagebreak');
            const htmlContent = await page.locator('body').innerHTML();
            expect(htmlContent).toContain('<pagebreak>');
        });

        test('dkpdf-columns shortcode works', async ({ page }) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_post').check();
            await page.getByRole('button', { name: 'Save Settings' }).click();

            // Edit the default "Hello world" post to add columns shortcode
            await page.goto('/wp-admin/post.php?post=1&action=edit');
            await page.click('#content-html');
            await page.fill('#content', 'Before columns [dkpdf-columns columns="2"]Column content here[/dkpdf-columns] After columns');
            await page.click('#publish');
            await page.waitForSelector('.notice-success');

            // Test in normal context - should have normal content without column markup
            await page.goto('/?p=1');
            await expect(page.locator('text=Before columns')).toBeVisible();
            await expect(page.locator('text=Column content here')).toBeVisible();
            await expect(page.locator('text=After columns')).toBeVisible();
            const normalContent = await page.locator('.entry-content').innerHTML();
            expect(normalContent).not.toContain('<columns');

            // Test in PDF HTML output context - should have column markup
            await page.goto('/?pdf=1&output=html');
            await expect(page.locator('body')).toContainText('Before columns');
            await expect(page.locator('body')).toContainText('Column content here');
            await expect(page.locator('body')).toContainText('After columns');
            const htmlContent = await page.locator('body').innerHTML();
            expect(htmlContent).toContain('<columns column-count="2"');
            expect(htmlContent).toContain('<columns column-count="1">');
        });
    });
});
