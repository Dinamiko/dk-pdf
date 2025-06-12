# DK PDF

### How to run e2e tests

```
npm install
npm run wp-env start

# Clean testing environment
wp-env clean tests

# Run all tests
npx playwright test

# Run a test file
npx playwright test tests/e2e/pdf-generation.spec.js

# Run opening the browser
npx playwright test --headed

# Run with debugging
npx playwright test --debug

# Run specific test
npx playwright test -g "PDF button generates PDF for posts"
```
