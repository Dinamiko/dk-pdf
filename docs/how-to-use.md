# How to use DK PDF templates in your Theme

The template files can be found within the `/plugins/dk-pdf/templates/` directory.

### Override templates in Theme
You can customize DK PDF template files in an upgrade safe way through overrides. Simply copy the desired templates into a directory within your theme named /dkpdf, keeping the same file structure.

Example:

```
Copy plugins/dk-pdf/templates/dkpdf-index.php to themes/your-theme/dkpdf/dkpdf-index.php
```
[See How to add custom fields to PDF using ACF Tutorial](pdf-acf.md)

### Override templates in Child Theme

DK PDF allows override templates in Child Themes too.

Example:

```
Copy plugins/dk-pdf/templates/dkpdf-index.php to themes/your-child-theme/dkpdf/dkpdf-index.php
```
[More info about Child Themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/) (WordPress Theme Handbook)