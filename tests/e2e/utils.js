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
