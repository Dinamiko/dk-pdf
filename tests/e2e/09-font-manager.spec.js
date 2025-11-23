// @ts-check
import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './utils';
import { execSync } from 'node:child_process';
import path from 'path';

test.describe('Font Manager', () => {
    test.beforeEach(async ({ page }) => {
        try {
            execSync('npx wp-env run tests-cli -- bash -c "rm -rf wp-content/uploads/dkpdf-fonts"', { stdio: 'pipe' });
        } catch (error) {
            // Directory might not exist, that's ok
        }

        await loginAsAdmin(page);
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=dkpdf_setup');

        // Wait for page to fully load
        await page.waitForLoadState('networkidle');
    });

    test('installs core fonts from GitHub', async ({ page }) => {
        await expect(page.locator('#dkpdf-download-fonts')).toBeVisible();
        await page.click('#dkpdf-download-fonts');
        await expect(page.locator('#dkpdf-download-progress')).toBeVisible();
        await expect(page.locator('#dkpdf-download-status .notice-success')).toBeVisible({ timeout: 10000 });

        // Reload page to see font selector
        await page.reload();

        // Verify font dropdown appears with fonts
        const fontDropdown = page.locator('#dkpdf_font_downloader');
        await expect(fontDropdown).toBeVisible();

        // Verify dropdown has options (core fonts installed)
        const optionsCount = await fontDropdown.locator('option').count();
        expect(optionsCount).toBeGreaterThan(0);

        // Verify some expected core fonts are present
        await expect(fontDropdown.locator('option[value="DejaVuSans"]')).toBeAttached();
        await expect(fontDropdown.locator('option[value="FreeSans"]')).toBeAttached();
    });

    test('displays font list with category badges', async ({ page }) => {
        await page.click('#dkpdf-download-fonts');
        await expect(page.locator('#dkpdf-download-status .notice-success')).toBeVisible({ timeout: 10000 });
        await page.reload();

        await page.click('#dkpdf-manage-fonts');
        await expect(page.locator('.dkpdf-modal-overlay')).toBeVisible();
        await expect(page.locator('.dkpdf-loading')).not.toBeVisible({ timeout: 2000 });

        // Verify font list has items
        const fontItems = page.locator('.dkpdf-font-item');
        const fontCount = await fontItems.count();
        expect(fontCount).toBeGreaterThan(0);

        // Verify core fonts have "Core" badge
        const coreBadgeCount = await page.locator('.dkpdf-badge-core').count();
        expect(coreBadgeCount).toBeGreaterThan(0);

        // Verify category badges appear (Unicode for DejaVu/Free fonts)
        const categoryBadgeCount = await page.locator('.dkpdf-badge-category').count();
        expect(categoryBadgeCount).toBeGreaterThan(0);

        // Verify at least one font has "Active" badge (selected font)
        await expect(page.locator('.dkpdf-badge-active')).toHaveCount(1);

        // Verify the selected font item has disabled delete button
        const selectedFont = page.locator('.dkpdf-font-selected');
        await expect(selectedFont.locator('.dkpdf-delete-font')).toBeDisabled();
    });

    test('uploads custom font', async ({ page }) => {
        await page.click('#dkpdf-download-fonts');
        await expect(page.locator('#dkpdf-download-status .notice-success')).toBeVisible({ timeout: 10000 });
        await page.reload();
        await page.click('#dkpdf-manage-fonts');
        await expect(page.locator('.dkpdf-modal-overlay')).toBeVisible();
        await expect(page.locator('.dkpdf-loading')).not.toBeVisible({ timeout: 2000 });

        // Get initial font count
        const initialCount = await page.locator('.dkpdf-font-item').count();

        // Upload Montserrat-Bold.ttf
        const testFontPath = path.join(__dirname, 'Montserrat-Bold.ttf');
        await page.setInputFiles('#dkpdf-font-file-input', testFontPath);
        await expect(page.locator('.dkpdf-modal-message .notice-success')).toBeVisible({ timeout: 2000 });
        await expect(page.locator('.dkpdf-modal-message')).toContainText('uploaded successfully');

        // Wait for font list to reload
        await expect(page.locator('.dkpdf-loading')).not.toBeVisible({ timeout: 3000 });

        // Verify new font appears in list
        await expect(page.locator('.dkpdf-font-item')).toHaveCount(initialCount + 1);

        // Verify the uploaded font has "Custom" badge
        const uploadedFont = page.locator('.dkpdf-font-item').filter({ hasText: 'Montserrat-Bold' });
        await expect(uploadedFont.locator('.dkpdf-badge-custom')).toBeVisible();

        // Verify font dropdown updated (without page reload)
        const fontDropdown = page.locator('#dkpdf_font_downloader');
        await expect(fontDropdown.locator('option[value="Montserrat-Bold"]')).toBeAttached();
    });

    test('deletes custom font', async ({ page }) => {
        await page.click('#dkpdf-download-fonts');
        await expect(page.locator('#dkpdf-download-status .notice-success')).toBeVisible({ timeout: 10000 });
        await page.reload();
        await page.click('#dkpdf-manage-fonts');
        await expect(page.locator('.dkpdf-modal-overlay')).toBeVisible();
        await expect(page.locator('.dkpdf-loading')).not.toBeVisible({ timeout: 3000 });

        const testFontPath = path.join(__dirname, 'Montserrat-Bold.ttf');
        await page.setInputFiles('#dkpdf-font-file-input', testFontPath);
        await expect(page.locator('.dkpdf-modal-message .notice-success')).toBeVisible({ timeout: 3000 });

        // Wait for font list to reload after upload
        await expect(page.locator('.dkpdf-loading')).not.toBeVisible({ timeout: 3000 });

        // Get count before deletion (after upload has completed)
        const countBeforeDelete = await page.locator('.dkpdf-font-item').count();

        // Find the uploaded font
        const uploadedFont = page.locator('.dkpdf-font-item').filter({ hasText: 'Montserrat-Bold' });

        // Set up dialog handler to accept confirmation
        page.on('dialog', dialog => dialog.accept());

        await uploadedFont.locator('.dkpdf-delete-font').click();

        await expect(page.locator('.dkpdf-modal-message .notice-success')).toBeVisible({ timeout: 3000 });
        await expect(page.locator('.dkpdf-modal-message')).toContainText('deleted successfully');
        await expect(page.locator('.dkpdf-loading')).not.toBeVisible({ timeout: 3000 });

        // Verify font is removed from list
        await expect(page.locator('.dkpdf-font-item')).toHaveCount(countBeforeDelete - 1);

        // Verify font no longer appears in the list
        await expect(page.locator('.dkpdf-font-item').filter({ hasText: 'Montserrat-Bold' })).toHaveCount(0);

        // Verify font dropdown updated
        const fontDropdown = page.locator('#dkpdf_font_downloader');
        await expect(fontDropdown.locator('option[value="Montserrat-Bold"]')).toHaveCount(0);
    });
});
