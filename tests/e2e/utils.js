import {expect} from "@playwright/test";
import {execSync} from 'child_process';

export async function loginAsAdmin(page) {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
}

/**
 * Helper function to create a test user using wp-cli
 */
export async function createTestUser(page, username, role = 'subscriber') {
    try {
        execSync(
            `npx wp-env run tests-cli -- wp user create ${username} ${username}@example.com --role=${role} --user_pass=testpassword123`,
            { stdio: 'pipe' }
        );
    } catch (error) {
        console.log(`Note: User ${username} might already exist`);
    }
}

/**
 * Helper function to delete a test user using wp-cli
 */
export async function deleteTestUser(page, username) {
    try {
        execSync(
            `npx wp-env run tests-cli -- wp user delete ${username} --yes`,
            { stdio: 'pipe' }
        );
    } catch (error) {
        console.log(`Note: User ${username} might not exist`);
    }
}

/**
 * Helper function to login as a specific user
 */
export async function loginAsUser(page, username, password = 'testpassword123') {
    await page.goto('/wp-login.php?action=logout');
    await page.goto('/wp-login.php');
    await page.fill('#user_login', username);
    await page.fill('#user_pass', password);
    await page.click('#wp-submit');
}

/**
 * Helper function to logout
 */
export async function logout(page) {
    await page.goto('/wp-login.php?action=logout');
    const confirmButton = page.locator('a:has-text("log out")');
    if (await confirmButton.count() > 0) {
        await confirmButton.click();
    }
}

export async function getProductUrl(productName) {
    const productUrls = {
        'Test Laptop': '/product/test-laptop/',
        'JavaScript Guide': '/product/javascript-guide/',
        'Wireless Mouse': '/product/wireless-mouse/'
    };

    return productUrls[productName] || null;
}

export async function getCategoryUrl(categorySlug) {
    const categoryUrls = {
        'electronics': '/product-category/electronics/',
        'books': '/product-category/books/'
    };

    return categoryUrls[categorySlug] || `/product-category/${categorySlug}/`;
}

export async function enableWooCommerceProductDisplay(page, options = 'all') {
    await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
    await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');

    await page.getByRole('button', {name: 'Save Settings'}).click();

    const singleProductOptions = [
        'wc_product_display_title',
        'wc_product_display_description',
        'wc_product_display_price',
        'wc_product_display_sku',
        'wc_product_display_product_img',
        'wc_product_display_categories'
    ];

    const archiveProductOptions = [
        'wc_archive_display_title',
        'wc_archive_display_price',
        'wc_archive_display_product_thumbnail',
        'wc_archive_display_sku'
    ];

    let optionsToEnable = [];

    if (options === 'all' || options === 'single') {
        optionsToEnable = optionsToEnable.concat(singleProductOptions);
    }

    if (options === 'all' || options === 'archive') {
        optionsToEnable = optionsToEnable.concat(archiveProductOptions);
    }

    for (const option of optionsToEnable) {
        const checkbox = page.locator(`#${option}`);
        if (await checkbox.count() > 0) {
            await checkbox.check();
        }
    }

    await page.getByRole('button', {name: 'Save Settings'}).click();
    await expect(page.locator('.updated')).toContainText('Settings saved');
}
