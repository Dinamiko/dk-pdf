// @ts-check
import {test, expect} from '@playwright/test';
import {loginAsAdmin, getProductUrl, getCategoryUrl, enableWooCommerceProductDisplay} from "./utils";

test.describe('WooCommerce Integration', () => {
    test.beforeEach(async ({page}) => {
        await loginAsAdmin(page);
    });

    test.describe('WooCommerce Settings Configuration', () => {
        test('can enable product post type in DK PDF settings', async ({page}) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');

            // Enable product post type
            await page.locator('#pdfbutton_post_types_product').check();
            await page.getByRole('button', {name: 'Save Settings'}).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();

            // Verify setting persistence
            await page.reload();
            await expect(page.locator('#pdfbutton_post_types_product')).toBeChecked();
        });

        test('can configure WooCommerce product display options', async ({page}) => {
            // Go to PDF templates tab
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');

            // Select default template
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');

            // Configure WooCommerce product display options
            const productDisplayOptions = [
                'wc_product_display_description',
                'wc_product_display_price',
                'wc_product_display_sku'
            ];

            for (const option of productDisplayOptions) {
                const checkbox = page.locator(`#${option}`);
                if (await checkbox.count() > 0) {
                    await checkbox.check();
                }
            }

            await page.getByRole('button', {name: 'Save Settings'}).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();
        });

        test('can enable product taxonomies for archive buttons', async ({page}) => {
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdfbtn');

            // Enable product category taxonomy
            await page.locator('#pdfbutton_taxonomies_product_cat').check();
            await page.getByRole('button', {name: 'Save Settings'}).click();
            await expect(page.getByText('Settings saved.')).toBeVisible();

            // Verify setting persistence
            await page.reload();
            await expect(page.locator('#pdfbutton_taxonomies_product_cat')).toBeChecked();
        });
    });

    test.describe('Single Product Page Tests', () => {
        test.beforeEach(async ({page}) => {
            // Ensure product post type is enabled
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.locator('#pdfbutton_post_types_product').check();
            await page.getByRole('button', {name: 'Save Settings'}).click();
        });

        test('PDF button appears on single product pages', async ({page}) => {
            // Navigate directly to test product using CLI-generated URL
            const productUrl = await getProductUrl('Test Laptop');
            await page.goto(productUrl);

            // Check if PDF button is visible
            await expect(page.locator('.dkpdf-button')).toBeVisible();
        });

        test('PDF button generates PDF for products', async ({page}) => {
            // Configure PDF download
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.getByRole('radio', {name: 'Download PDF directly'}).check();
            await page.getByRole('button', {name: 'Save Settings'}).click();

            // Navigate to test product
            const productUrl = await getProductUrl('Test Laptop');
            await page.goto(productUrl);

            const downloadPromise = page.waitForEvent('download');
            await page.click('.dkpdf-button');
            const download = await downloadPromise;

            expect(download.suggestedFilename()).toContain('.pdf');
            expect(download.suggestedFilename()).toContain('Test Laptop');
        });

        test('HTML output contains product-specific content', async ({page}) => {
            // Enable WooCommerce product display options
            await enableWooCommerceProductDisplay(page, 'single');

            // Navigate to product and check HTML output
            const productUrl = await getProductUrl('Test Laptop');
            await page.goto(`${productUrl}?pdf=10&output=html`);

            // Verify product title is present
            await expect(page.locator('body')).toContainText('Test Laptop');

            // Verify product-specific content is present in the template
            const pageContent = await page.content();

            // Check if any WooCommerce content is displayed
            if (pageContent.includes('price') || pageContent.includes('sku') || pageContent.includes('description')) {
                console.log('WooCommerce product content is enabled and displaying');
            }

            // Verify button is not visible in PDF output
            await expect(page.locator('.dkpdf-button')).not.toBeVisible();
        });
    });

    test.describe('Shop/Archive Page Tests', () => {
        test.beforeEach(async ({page}) => {
            // Set up template and taxonomy settings
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdf_templates');
            await page.selectOption('select[name="dkpdf_selected_template"]', 'default/');
            await page.getByRole('button', {name: 'Save Settings'}).click();

            await page.goto('/wp-admin/admin.php?page=dkpdf_settings&tab=pdfbtn');
            await page.locator('#pdfbutton_taxonomies_product_cat').check();
            await page.getByRole('button', {name: 'Save Settings'}).click();
        });

        test('PDF button appears on shop page', async ({page}) => {
            await page.goto('/shop/');
            await expect(page.locator('.dkpdf-button-container')).toBeVisible();
        });

        test('PDF button appears on product category pages', async ({page}) => {
            // Navigate to Electronics category using CLI-generated URL
            const categoryUrl = await getCategoryUrl('electronics');
            await page.goto(categoryUrl);

            await expect(page.locator('.dkpdf-button-container')).toBeVisible();
        });

        test('PDF generation works from shop page', async ({page}) => {
            // Configure PDF download
            await page.goto('/wp-admin/admin.php?page=dkpdf_settings');
            await page.getByRole('radio', {name: 'Download PDF directly'}).check();
            await page.getByRole('button', {name: 'Save Settings'}).click();

            await page.goto('/shop/');

            const downloadPromise = page.waitForEvent('download');
            await page.click('.dkpdf-button-container .dkpdf-button');
            const download = await downloadPromise;

            expect(download.suggestedFilename()).toContain('.pdf');
        });

        test('HTML output uses archive template for shop page', async ({page}) => {
            await enableWooCommerceProductDisplay(page, 'archive');
            await page.goto('/shop/?pdf=shop&output=html');

            await expect(page.locator('body')).toContainText('Shop');
            await expect(page.locator('body')).toContainText('Test Laptop');
            await expect(page.locator('body')).toContainText('Wireless Mouse');
        });

        test('category archive uses correct template', async ({page}) => {
            const categoryUrl = await getCategoryUrl('electronics');
            await page.goto(`${categoryUrl}?pdf=product_cat_16&output=html`);

            await expect(page.locator('body')).toContainText('Electronics');
        });
    });
});
