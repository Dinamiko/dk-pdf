# DK PDF

### Install
```
composer install
npm install
```

### Run local environment
```
npm run wp-env start
```

### Clean environments
```
wp-env clean development
wp-env clean tests
```

### How to run e2e tests
```
# Run all tests
npm run tests:e2e

# Run a test file
npm run tests:e2e 01-pdf-generation.spec.js

# Run a single test
npm run tests:e2e -- -g "HTML output uses archive template for shop page"

# Generate screenshot in test for debugging
await page.screenshot({ path: 'test-results/screeshot.png', fullPage: true });
```

### How to run PHPUnit unit and integration tests
```
npm run tests:unit
npm run tests:integration
```

### How to run Composer
```
wp-env run cli --env-cwd=wp-content/plugins/dk-pdf composer
```


