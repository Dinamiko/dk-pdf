=== DK PDF ===
Contributors: dinamiko
Tags: wp to pdf, wordpress to pdf, acrobat, pdf, post to pdf, generate pdf, mpdf, generate, convert, create, convert pdf, create pdf
Requires at least: 3.9.6
Tested up to: 4.8
Stable tag: 1.9.1

WordPress to PDF made easy.

== Description ==

DK PDF allows site visitors convert posts and pages to PDF using a button.

https://vimeo.com/148082260
<a href="http://wp.dinamiko.com/demos/dkpdf" target="_blank">See Demo</a>

= Features =

* Add PDF button in posts (including custom post types) and pages.
* Configure PDF header and footer, add custom logo, custom CSS and more.
* Copy plugin templates in your theme for PDF customizations.
* [dkpdf-remove] shortcode for removing pieces of content.
* [dkpdf-pagebreak] shortcode for adding page breaks.
* Add PDF button manually using [dkpdf-button] shortcode.
* Remove PDF button using a checkbox.

= Addons =
* Do you need to create a PDF with a selection of your articles?
<a href="http://codecanyon.net/item/dk-pdf-generator/13530581" target="_blank">DK PDF Generator</a> is the perfect tool for you.

= Documentation =
* <a href="http://wp.dinamiko.com/demos/dkpdf/documentation" target="_blank">See Documentation</a>

= Related Plugins by Dinamiko =
* <a href="https://wordpress.org/plugins/dk-white-label/">DK White Label</a>, white label and branding WordPress admin.
* <a href="https://wordpress.org/plugins/dportfolio/">DPortfolio</a>, portfolio manager.
* <a href="https://wordpress.org/plugins/docu/">Docu</a>, documentation plugin.

= Collaborate in Github =
* <a href="https://github.com/Dinamiko/dk-pdf" target="_blank">https://github.com/Dinamiko/dk-pdf</a>

== Installation ==

Installing "DK PDF" can be done either by searching for "DK PDF" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
2. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
3. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Front-end PDF Button
2. PDF Button settings
3. PDF Setup settings
4. PDF Header & Footer settings
5. PDF Custom CSS
6. Disable PDF Button Metabox

== Credits ==

Thanks to:

mPDF, PHP class which generates PDF files from UTF-8 encoded HTML
http://www.mpdf1.com/mpdf/index.php

Font Awesome, the iconic font and CSS toolkit
http://fortawesome.github.io/Font-Awesome/

== Changelog ==
= 1.9.1 =
* Added PDF Protection in PDF Setup Settings
* New Columns Shortcodes: [dkpdf-columns] and [dkpdf-columnbreak]
* New Filter: dkpdf_pdf_filename
* Fixed Admin scripts enqueued on all pages (thanks to Aristeides Stathopoulos @aristath)
= 1.9 =
* Added shortcode tag attribute to dkpdf-remove shortcode
* FontAwesome icons support
* Added post title as PDF filename when downloaded from browser
= 1.8 =
* New filter dkpdf_pdf_format
* New filter dkpdf_header_title
* Option for remove default PDF button when adding PDF button manually (thanks to Renato Alves)
= 1.7 =
* New filters (see documentation filters)
* Fixed github issues #21 #23 #24
= 1.6 =
* 4.4.2 Tested
* Added DK PDF Generator compatibility (css + shortcodes)
= 1.5 =
* Added PDF Custom CSS setting
* Sanitized settings fields
= 1.4 =
* Added [dkpdf-pagebreak] shortcode for adding page breaks
* Added filters dkpdf_header_pagination and dkpdf_footer_pagination
* Added addons page to admin menu
= 1.3 =
* New DK PDF admin menu for better usability
* Added a PDF Setup tab for adjusting page orientation, font size and margins of the PDF
* Added [dkpdf-remove] shortcode for removing pieces of content in the generated PDF
= 1.2 =
* Settings link in plugins list page
* Adjusts header template for better logo display
= 1.1 =
* Removes dkpdf-button shortcode in the generated PDF
= 1.0 =
* Initial release
