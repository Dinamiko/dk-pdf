import {expect} from "@playwright/test";

export async function loginAsAdmin(page) {
    await page.goto('/wp-login.php');
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
}

export async function getProductUrl(productName) {
    // Use static URLs based on existing product data
    const productUrls = {
        'Test Laptop': '/product/test-laptop/',
        'JavaScript Guide': '/product/javascript-guide/',
        'Wireless Mouse': '/product/wireless-mouse/'
    };

    return productUrls[productName] || null;
}

export async function getCategoryUrl(categorySlug) {
    // Use static URLs based on existing category data
    const categoryUrls = {
        'electronics': '/product-category/electronics/',
        'books': '/product-category/books/'
    };

    return categoryUrls[categorySlug] || `/product-category/${categorySlug}/`;
}

export async function enableWooCommerceProductDisplay(page, options = 'all') {
    // Navigate to PDF templates tab
    await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
    await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');

    await page.getByRole('button', {name: 'Save Settings'}).click();

    // Define available display options
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

    // Enable the specified options
    for (const option of optionsToEnable) {
        const checkbox = page.locator(`#${option}`);
        if (await checkbox.count() > 0) {
            await checkbox.check();
        }
    }

    await page.getByRole('button', {name: 'Save Settings'}).click();
    await expect(page.locator('.updated')).toContainText('Settings saved');
}
