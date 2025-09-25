import {execSync} from "node:child_process";

execSync('wp-env run cli -- wp rewrite structure "/%postname%/" --hard', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp rewrite structure "/%postname%/" --hard', { stdio: 'inherit' });

execSync('wp-env run cli -- wp theme activate storefront', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp theme activate storefront', { stdio: 'inherit' });

execSync('wp-env run cli -- wp plugin activate disable-rest-permissions', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp plugin activate disable-rest-permissions', { stdio: 'inherit' });

execSync('wp-env run cli -- wp option update woocommerce_coming_soon "no"', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp option update woocommerce_coming_soon "no"', { stdio: 'inherit' });

execSync('wp-env run cli --  wp option update woocommerce_onboarding_profile \'{"skipped": "true"}\' --format=json', { stdio: 'inherit' });
execSync('wp-env run tests-cli --  wp option update woocommerce_onboarding_profile \'{"skipped": "true"}\' --format=json', { stdio: 'inherit' });

// Create product categories using WP-CLI
execSync('npm run wp-env run tests-cli -- wp wc product_cat create --name="Electronics" --slug="electronics" --description="Electronic products and gadgets"', { stdio: 'inherit' });
execSync('npm run wp-env run tests-cli -- wp wc product_cat create --name="Books" --slug="books" --description="Books and literature"', { stdio: 'inherit' });

// Create sample products using WP-CLI
execSync('npm run wp-env run tests-cli -- wp wc product create --name="Test Laptop" --type=simple --regular_price="999.99" --description="A high-quality test laptop for development" --sku="TEST-LAPTOP-001" --categories="[{\\"id\\":\\"16\\"}]"', { stdio: 'inherit' });
execSync('npm run wp-env run tests-cli -- wp wc product create --name="JavaScript Guide" --type=simple --regular_price="29.99" --description="Complete guide to JavaScript programming" --sku="TEST-BOOK-001" --categories="[{\\"id\\":\\"17\\"}]"', { stdio: 'inherit' });
execSync('npm run wp-env run tests-cli -- wp wc product create --name="Wireless Mouse" --type=simple --regular_price="49.99" --description="Ergonomic wireless mouse for productivity" --sku="TEST-MOUSE-001" --categories="[{\\"id\\":\\"16\\"}]"', { stdio: 'inherit' });
