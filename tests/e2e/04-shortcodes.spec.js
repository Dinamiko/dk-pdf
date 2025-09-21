// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Shortcode Functionality', () => {
    test.beforeEach(async ({page}) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'password');
        await page.click('#wp-submit');
    });

    test('dkpdf-button shortcode works in PDF context', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('radio', {name: 'Use shortcode'}).check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post-new.php');
        await page.fill('#title', 'Button Shortcode Test');
        await page.click('#content-html');
        await page.fill('#content', 'Before shortcode [dkpdf-button] After shortcode');
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        const postUrl = await page.locator('.notice-success a').getAttribute('href');
        const postId = new URL(postUrl).searchParams.get('p');

        // Test in PDF HTML output context
        await page.goto(`/?p=${postId}&pdf=${postId}&output=html`);
        await expect(page.locator('body')).toContainText('Before shortcode');
        await expect(page.locator('body')).toContainText('After shortcode');

        // Test in normal context
        await page.goto(postUrl);
        await expect(page.locator('.dkpdf-button')).toBeVisible();
        await expect(page.locator('text=Before shortcode')).toBeVisible();
        await expect(page.locator('text=After shortcode')).toBeVisible();
    });

    test('dkpdf-remove shortcode removes content in PDF context', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post-new.php');
        await page.fill('#title', 'Remove Shortcode Test');
        await page.click('#content-html');
        await page.fill('#content', 'Before remove [dkpdf-remove]Gallery content to remove[/dkpdf-remove] After remove');
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        const postUrl = await page.locator('.notice-success a').getAttribute('href');
        const postId = new URL(postUrl).searchParams.get('p');

        // Test in PDF HTML output context - content should be removed
        await page.goto(`/?p=${postId}&pdf=${postId}&output=html`);
        await expect(page.locator('body')).toContainText('Before remove');
        await expect(page.locator('body')).toContainText('After remove');
        await expect(page.locator('body')).not.toContainText('Gallery content to remove');

        // Test in normal context - content should be visible
        await page.goto(postUrl);
        await expect(page.locator('text=Before remove')).toBeVisible();
        await expect(page.locator('text=After remove')).toBeVisible();
        await expect(page.locator('text=Gallery content to remove')).toBeVisible();
    });

    test('dkpdf-pagebreak shortcode adds pagebreak in PDF context', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post-new.php');
        await page.fill('#title', 'Pagebreak Shortcode Test');
        await page.click('#content-html');
        await page.fill('#content', 'Before pagebreak [dkpdf-pagebreak] After pagebreak');
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        const postUrl = await page.locator('.notice-success a').getAttribute('href');
        const postId = new URL(postUrl).searchParams.get('p');

        // Test in PDF HTML output context - should have pagebreak markup
        await page.goto(`/?p=${postId}&pdf=${postId}&output=html`);
        await expect(page.locator('body')).toContainText('Before pagebreak');
        await expect(page.locator('body')).toContainText('After pagebreak');
        const htmlContent = await page.locator('body').innerHTML();
        expect(htmlContent).toContain('<pagebreak>');
        expect(htmlContent).toContain('</pagebreak');

        // Test in normal context - should not have pagebreak markup
        await page.goto(postUrl);
        await expect(page.locator('text=Before pagebreak')).toBeVisible();
        await expect(page.locator('text=After pagebreak')).toBeVisible();
        const normalContent = await page.locator('.entry-content').innerHTML();
        expect(normalContent).not.toContain('<pagebreak>');
        expect(normalContent).not.toContain('</pagebreak>');
    });

    test('dkpdf-columns shortcode adds column markup in PDF context', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post-new.php');
        await page.fill('#title', 'Columns Shortcode Test');
        await page.click('#content-html');
        await page.fill('#content', 'Before columns [dkpdf-columns columns="2"]Column content here[/dkpdf-columns] After columns');
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        const postUrl = await page.locator('.notice-success a').getAttribute('href');
        const postId = new URL(postUrl).searchParams.get('p');

        // Test in PDF HTML output context - should have column markup
        await page.goto(`/?p=${postId}&pdf=${postId}&output=html`);
        await expect(page.locator('body')).toContainText('Before columns');
        await expect(page.locator('body')).toContainText('Column content here');
        await expect(page.locator('body')).toContainText('After columns');
        const htmlContent = await page.locator('body').innerHTML();
        expect(htmlContent).toContain('<columns column-count="2"');
        expect(htmlContent).toContain('<columns column-count="1">');

        // Test in normal context - should have normal content without column markup
        await page.goto(postUrl);
        await expect(page.locator('text=Before columns')).toBeVisible();
        await expect(page.locator('text=Column content here')).toBeVisible();
        await expect(page.locator('text=After columns')).toBeVisible();
        const normalContent = await page.locator('.entry-content').innerHTML();
        expect(normalContent).not.toContain('<columns');
    });

    test('dkpdf-columnbreak shortcode adds columnbreak in PDF context', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post-new.php');
        await page.fill('#title', 'Columnbreak Shortcode Test');
        await page.click('#content-html');
        await page.fill('#content', '[dkpdf-columns]First column content[dkpdf-columnbreak]Second column content[/dkpdf-columns]');
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        const postUrl = await page.locator('.notice-success a').getAttribute('href');
        const postId = new URL(postUrl).searchParams.get('p');

        // Test in PDF HTML output context - should have columnbreak markup
        await page.goto(`/?p=${postId}&pdf=${postId}&output=html`);

        await page.screenshot({ path: 'test-results/columnbreak-pdf-output.png', fullPage: true });

        await expect(page.locator('body')).toContainText('First column content');
        await expect(page.locator('body')).toContainText('Second column content');
        const htmlContent = await page.locator('body').innerHTML();
        expect(htmlContent).toContain('<columnbreak>');
        expect(htmlContent).toContain('</columnbreak>');

        // Test in normal context - should have normal content without columnbreak markup
        await page.goto(postUrl);
        await expect(page.locator('text=First column content')).toBeVisible();
        await expect(page.locator('text=Second column content')).toBeVisible();
        const normalContent = await page.locator('.entry-content').innerHTML();
        expect(normalContent).not.toContain('<columnbreak>');
        expect(normalContent).not.toContain('</columnbreak>');
    });
});
