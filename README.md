# DK PDF

### Install
```
composer install
npm install
```

### Build Assets
The plugin uses modern JavaScript (ES6+) and SCSS, which need to be compiled before use.

```bash
# Build production assets (minified)
npm run build:assets

# Development mode with watch (auto-rebuild on file changes)
npm start
```

**Source files**: `resources/js/` and `resources/css/`
**Built files**: `build/` (gitignored, auto-generated)

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

### Development Workflow

1. **Install dependencies**: `npm install && composer install`
2. **Start development mode**: `npm start` (watches for file changes)
3. **Edit source files**:
   - JavaScript: `resources/js/`
   - CSS/SCSS: `resources/css/`
4. **Files auto-compile** to `build/` directory
5. **Run tests** before committing

### Build for Production
```bash
# Full production build (includes asset compilation)
npm run build

# Asset compilation only
npm run build:assets
```

### Architecture

**JavaScript**:
- Modern ES6+ with vanilla JavaScript (no jQuery)
- Modular structure with ES modules
- Native Fetch API for AJAX requests
- Built with webpack via `@wordpress/scripts`

**CSS**:
- SCSS with modern `@use` syntax
- CSS variables for theming
- Modular component structure
- Auto-generated RTL stylesheets
- Autoprefixed for browser compatibility

**Third-party libraries** (kept as-is):
- Select2 for enhanced dropdowns
- Farbtastic for color picker
- ACE Editor for CSS editing


