// @ts-check
import {test, expect} from '@playwright/test';

test.describe('PDF Button Display', () => {
    test.beforeEach(async ({page}) => {
        await page.goto('/wp-login.php');
        await page.fill('#user_login', 'admin');
        await page.fill('#user_pass', 'password');
        await page.click('#wp-submit');
    });

    test('button appears on single post when post type is enabled', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button')).toBeVisible();
    });

    test('button appears on category archive when taxonomy is enabled', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
        await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdfbtn');
        await page.locator('#pdfbutton_taxonomies_category').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await page.goto('/?cat=1');
        await expect(page.locator('.dkpdf-button-container')).toBeVisible();
    });

    test('button does not appear when post type is disabled', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').uncheck();
        await page.locator('#pdfbutton_post_types_page').uncheck();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button')).not.toBeVisible();
    });

    test('button position settings work correctly', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();

        await page.getByRole('radio', {name: 'Before content'}).check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/?p=1');
        const buttonElement = page.locator('.dkpdf-button-container');
        await expect(buttonElement).toBeVisible();

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.getByRole('radio', {name: 'After content'}).check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/?p=1');
        await expect(buttonElement).toBeVisible();
    });

    test('button can be disabled per post via metabox', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/post.php?post=1&action=edit');

        await page.getByLabel('', {exact: true}).check();
        await page.click('#publish');
        await page.waitForSelector('.notice-success');

        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button')).not.toBeVisible();
    });
});
