# Changelog

## 2.0.2
- Fix - Wrong post content in single templates.

## 2.0.1
- Fix - Broken shortcodes after 2.0.0 upgrade.

## 2.0.0
- Enhancement - New template system and content display including archive and single templates.
- Enhancement - Support for WooCommerce products including single, shop and archive pages.

## 1.9.10
- Enhancement - Add new template sets system.
- Enhancement - Add new `dkpdf_content_template` filter to select template conditionally.
- Enhancement - Add output PDF html for debugging purposes.
- Enhancement - Increase init settings action priority to allow more custom post types to apply.

## 1.9.9
- Fix - Plugin templates not displaying CSS correctly.

## 1.9.8
- Fix - Add custom namespace to avoid issues with third party composer packages.
- Fix - Disable deprecated dynamic property message.

## 1.9.7
- Enhancement - Update plugin requirements and mpdf library to PHP 8+
- Fix - Reflected Cross-Site Scripting security issue.

## 1.9.6
- New filters `dkpdf_mpdf_font_dir`, `dkpdf_mpdf_font_data`, `dkpdf_mpdf_temp_dir`. Thanks to [joostvanbockel](https://github.com/joostvanbockel).

## 1.9.3
- Reverting to 1.9.1, something went wrong in 1.9.2.

## 1.9.2
- PHP7: Remove some warnings, see [issue #38](https://github.com/Dinamiko/dk-pdf/issues/38), [issue #48](https://github.com/Dinamiko/dk-pdf/issues/48).
- Task Runner: Add Gulp and `zip` task, see [issue #50](https://github.com/Dinamiko/dk-pdf/issues/50).
- HTTPS: Fix images not working after move to https, see [issue #51](https://github.com/Dinamiko/dk-pdf/issues/51).

## 1.9.1
- Added PDF Protection in PDF `Setup Settings`.
- New Columns Shortcodes: `[dkpdf-columns]` and `[dkpdf-columnbreak]`.
- New Filter: `dkpdf_pdf_filename`.
- Fixed Admin scripts enqueued on all pages (thanks to Aristeides Stathopoulos @aristath).

## 1.9
- Added shortcode tag attribute to `dkpdf-remove` shortcode.
- FontAwesome icons support.
- Added post title as PDF filename when downloaded from browser.

## 1.8
- New filter `dkpdf_pdf_format`.
- New filter `dkpdf_header_title`.
- Option for remove default PDF button when adding PDF button manually (thanks to Renato Alves).

## 1.7
- New filters (see documentation filters).
- Fixed github issues #21 #23 #24.

## 1.6
- 4.4.2 Tested.
- Added DK PDF Generator compatibility (css + shortcodes).

## 1.5
- Added PDF Custom CSS setting.
- Sanitized settings fields.

## 1.4
- Added `[dkpdf-pagebreak]` shortcode for adding page breaks.
- Added filters `dkpdf_header_pagination` and `dkpdf_footer_pagination`.
- Added addons page to admin menu.

## 1.3
- New DK PDF admin menu for better usability.
- Added a PDF Setup tab for adjusting page orientation, font size and margins of the PDF.
- Added `[dkpdf-remove]` shortcode for removing pieces of content in the generated PDF.

## 1.2
- Settings link in plugins list page.
- Adjusts header template for better logo display.

## 1.1
- Removes `dkpdf-button` shortcode in the generated PDF.

## 1.0
- Initial release.
