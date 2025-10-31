// @ts-check
import {test, expect} from '@playwright/test';
import {loginAsAdmin, createTestUser, deleteTestUser, loginAsUser, logout} from "./utils";

test.describe('PDF Button Display', () => {
    test.beforeEach(async ({page}) => {
        await loginAsAdmin(page);
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

        await page.screenshot({ path: 'test-results/screeshot.png', fullPage: true });

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

        await page.goto('/wp-admin/post.php?post=1&action=edit');
        await page.getByLabel('', {exact: true}).uncheck();
        await page.click('#publish');
        await page.waitForSelector('.notice-success');
    });
});

test.describe('PDF Button Visibility by Role', () => {
    test.beforeEach(async ({page}) => {
        await loginAsAdmin(page);

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        await page.locator('#pdfbutton_post_types_post').check();

        const visibilityField = page.locator('#button_visibility_roles');
        if (await visibilityField.count() > 0) {
            await visibilityField.selectOption(['all']);
        }

        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();
    });

    test('visibility by role setting field appears in admin', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');

        const visibilityField = page.locator('#button_visibility_roles');
        await expect(visibilityField).toBeVisible();

        await expect(page.locator('th:has-text("Visibility by role")')).toBeVisible();
    });

    test('button visible to all users when All is selected (default)', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');

        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['all']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await page.goto('/?p=1');

        await expect(page.locator('.dkpdf-button-container')).toBeVisible();

        await logout(page);
        await page.goto('/?p=1');

        await expect(page.locator('.dkpdf-button-container')).toBeVisible();
    });

    test('button hidden from non-logged-in users when restricted to administrator role', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');

        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['administrator']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button-container')).toBeVisible();

        await logout(page);
        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button-container')).not.toBeVisible();
    });

    test('button visible to subscriber when restricted to subscriber role', async ({page}) => {
        const testUsername = 'testsubscriber';

        await createTestUser(page, testUsername, 'subscriber');

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['subscriber']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await loginAsUser(page, testUsername);
        await page.goto('/?p=1');

        await expect(page.locator('.dkpdf-button-container')).toBeVisible();

        await deleteTestUser(page, testUsername);
    });

    test('button hidden from subscriber when restricted to administrator role', async ({page}) => {
        const testUsername = 'testsubscriber2';

        await createTestUser(page, testUsername, 'subscriber');

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['administrator']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await loginAsUser(page, testUsername);
        await page.goto('/?p=1');

        await expect(page.locator('.dkpdf-button-container')).not.toBeVisible();

        await deleteTestUser(page, testUsername);
    });

    test('unauthorized user gets 403 when accessing PDF directly', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['administrator']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await logout(page);

        const response = await page.goto('/?p=1&pdf=1');

        await expect(page.locator('h1:has-text("Access Denied")')).toBeVisible();
        await expect(page.locator('p:has-text("You do not have permission to view this PDF")')).toBeVisible();
    });

    test('button visibility respects role setting on archive pages', async ({page}) => {
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
        await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
        await page.getByRole('button', {name: 'Save Settings'}).click();

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdfbtn');
        await page.locator('#pdfbutton_taxonomies_category').check();

        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['administrator']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await page.goto('/?cat=1');
        await expect(page.locator('.dkpdf-button-container')).toBeVisible();

        await logout(page);
        await page.goto('/?cat=1');
        await expect(page.locator('.dkpdf-button-container')).not.toBeVisible();
    });

    test('multiple roles can be selected for button visibility', async ({page}) => {
        const subscriberUsername = 'testsubscriber3';
        const editorUsername = 'testeditor';

        await createTestUser(page, subscriberUsername, 'subscriber');
        await createTestUser(page, editorUsername, 'editor');

        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        const visibilityField = page.locator('#button_visibility_roles');
        await visibilityField.selectOption(['subscriber', 'editor']);
        await page.getByRole('button', {name: 'Save Settings'}).click();
        await expect(page.getByText('Settings saved.')).toBeVisible();

        await loginAsUser(page, subscriberUsername);
        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button-container')).toBeVisible();

        await loginAsUser(page, editorUsername);
        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button-container')).toBeVisible();

        await logout(page);
        await page.goto('/?p=1');
        await expect(page.locator('.dkpdf-button-container')).not.toBeVisible();

        await deleteTestUser(page, subscriberUsername);
        await deleteTestUser(page, editorUsername);
    });

    test.afterEach(async ({page}) => {
        await loginAsAdmin(page);
        await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
        const visibilityField = page.locator('#button_visibility_roles');
        if (await visibilityField.count() > 0) {
            await visibilityField.selectOption(['all']);
            await page.getByRole('button', {name: 'Save Settings'}).click();
        }
    });
});
