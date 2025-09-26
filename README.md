# DK PDF

### How to run e2e tests
```
npm install
npm run wp-env start

# Clean environments
wp-env clean development
wp-env clean tests

# Run all tests
npm run tests:e2e

# Run a test file
npm run tests:e2e 01-pdf-generation.spec.js

# Run a single test
npm run tests:e2e -- -g "HTML output uses archive template for shop page"

# Generate screenshot in test for debugging
await page.screenshot({ path: 'test-results/screeshot.png', fullPage: true });
```

### How to run Composer
```
wp-env run cli --env-cwd=wp-content/plugins/dk-pdf composer
```
