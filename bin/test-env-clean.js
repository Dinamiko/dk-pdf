import {execSync} from "node:child_process";

execSync('wp-env run cli -- wp theme activate storefront', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp theme activate storefront', { stdio: 'inherit' });

execSync('wp-env run cli -- wp option update woocommerce_coming_soon "no"', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp option update woocommerce_coming_soon "no"', { stdio: 'inherit' });

execSync('wp-env run cli --  wp option update woocommerce_onboarding_profile \'{"skipped": "true"}\' --format=json', { stdio: 'inherit' });
execSync('wp-env run tests-cli --  wp option update woocommerce_onboarding_profile \'{"skipped": "true"}\' --format=json', { stdio: 'inherit' });

