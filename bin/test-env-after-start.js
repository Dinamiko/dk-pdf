import {execSync} from "node:child_process";

execSync('wp-env run cli -- wp theme activate storefront', { stdio: 'inherit' });
execSync('wp-env run tests-cli -- wp theme activate storefront', { stdio: 'inherit' });
