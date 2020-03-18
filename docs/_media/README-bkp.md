# Getting Started

## Install

Installing DK PDF can be done either by searching for `DK PDF` via the `Plugins > Add New` screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
2. Upload the ZIP file through the `Plugins > Add New > Upload` screen in your WordPress dashboard
3. Activate the plugin through the `Plugins` menu in WordPress

## Button Settings

![Button Settings screenshot](_images/button-settings.jpg "Button Settings")

### Button text
Text in the button, also can be modified in plugin dkpdf-button template, see [How to use DK PDF templates in your Theme]() for more info.

### Post types to apply
Select post types to show pdf button.

* post
* page
* attachment
* custom post types

### Action
What to do when pdf button is clicked.
* Open PDF in new Window
* Download PDF directly

### Position
Where button appears.
* Before content
* After content

### Align
Text align property of the button.
* Left
* Center
* Right

## PDF Setup Settings

![PDF Setup Settings screenshot](_images/setup-settings.jpg "PDF Setup Settings")

### Page orientation
Selects the format of the PDF: Horizontal or Vertical.

### Font size
Sets PDF font size, in points (pt).

### PDF Margins
Sets PDF margins, in points (pt).

### Enable PDF protection `Since: 1.9.1`
Encrypts and sets the PDF document permissions.

### Keep columns `Since: 1.9.1`
Used in dkpdf-columns shortcode. Columns will be written successively, there will be no balancing of the length of columns.

## Header & Footer Settings

![Header & Footer Settings screenshot](_images/header-footer-settings.jpg "Header & Footer Settings")

### Header logo
Upload any image you like here, for example a logo.

### Header show title
Displays page title in the header.

### Header show pagination
Displays pagination in the header.

### Footer text
Displays raw text or HTML markup in the footer.

### Footer show title
Displays page title in the footer.

### Footer show pagination
Displays pagination in the footer.

## CSS Settings

![CSS Settings screenshot](_images/css-settings.jpg "CSS Settings")

### PDF Custom CSS
Allow adding custom CSS to the PDF.

### Use your enqueued theme and plugin CSS
This option adds `wp_head()` to PDF `<head>`.

## Disable DK PDF Button in single post types

![Disable DK PDF Button in single post types screenshot](_images/disable-button.jpg "Disable DK PDF Button in single post types")

When activating a post type (post, page…) in `DK PDF Settings / PDF Button` the button is going to show in all posts of the selected post type, if you like to disable the PDF button in any particular post you have a metabox where you can check to not show it.

## Multilanguage / Plugin translations

### Multilanguage
Out of the box DK PDF has support for [Polylang multilingual plugin](https://wordpress.org/plugins/polylang/), all you need to do is go to Polylang `Strings translation` tab and you’ll see the fields with the strings ready to translate.

I didn’t test other multilanguage plugins but if the plugin you use has settings translation implemented in it, it will not be difficult to deal with this. Anyway if you are in trouble, please create a ticket in the DK PDF support forum.

### Plugin translations
DK PDF comes with a `languages` directory that contain .mo and .mo files from where you can create new language files with a tool like [Poedit](https://poedit.net/download).

# Customize

## How to use DK PDF templates in your Theme

The template files can be found within the `/plugins/dk-pdf/templates/` directory.

### Override templates in Theme
You can customize DK PDF template files in an upgrade safe way through overrides. Simply copy the desired templates into a directory within your theme named /dkpdf, keeping the same file structure.

Example:

```
Copy plugins/dk-pdf/templates/dkpdf-index.php to themes/your-theme/dkpdf/dkpdf-index.php
```
See How to add custom fields to PDF using ACF Tutorial

### Override templates in Child Theme

DK PDF allows override templates in Child Themes too.

Example:

```
Copy plugins/dk-pdf/templates/dkpdf-index.php to themes/your-child-theme/dkpdf/dkpdf-index.php
```
[More info about Child Themes](https://codex.wordpress.org/Child_Themes)

## How to add custom fonts

Since version 1.9.6, DK PDF allows you to add custom fonts to the PDF easily using `dkpdf_mpdf_font_dir` and `dkpdf_mpdf_font_data` filters.

In this example we are going to use Montserrat Google Font, so the first thing to do is [download the font from here](https://fonts.google.com/specimen/Montserrat).

![Monserrat Google Font screenshot](_images/montserrat-google-font.jpg "Monserrat Google Font")

In your WordPress installation, create a new `folder` fonts inside `wp-content` and upload `Montserrat-Regular.ttf` file:

![Add a font screenshot](_images/add-font.jpg "Add a font")

Next thing to do is add the filters, you can add this code to your theme `functions.php` for example:

```
<?php
/**
 *  Define the directory with the font via fontDir configuration key.
 */
add_filter( 'dkpdf_mpdf_font_dir', function ( $font_dir ) {
	// path to wp-content directory
	$wp_content_dir = trailingslashit( WP_CONTENT_DIR );
	array_push( $font_dir, $wp_content_dir . 'fonts' );
	return $font_dir;
});
/**
 * Define the font details in fontdata configuration variable
 */
add_filter( 'dkpdf_mpdf_font_data', function( $font_data ) {
	$font_data['montserrat'] = [
		'R' => 'Montserrat-Regular.ttf',
	];
	return $font_data;
});
```

Finally, create a new post and add this content:

```
Lorem <span style="font-family: Montserrat;">ipsum dolor</span> sit amet.
```

![Add a custom font style screenshot](_images/add-custom-font-style.jpg "Add a custom font style")

For the sake of simplicity, we are adding the css style inline directly in the post content, just keep in mind that you can add the styles in DK PDF Settings CSS tab.

That's all, you should now see Montserrat font displayed in the PDF like so:

![Font display PDF screenshot](_images/font-display-pdf.jpg "Font display PDF")

Here you'll find more information about how to add fonts in mPDF which is the library that DK PDF uses to generate the PDF: [Fonts in mPDF v7+](https://mpdf.github.io/fonts-languages/fonts-in-mpdf-7-x.html)

## FontAwesome icons support

In order to see FontAwesome icons in the PDF you’ve to use a code, see [FontAwesome Cheatsheet](https://fontawesome.com/cheatsheet/) page.

[![Font Awesome Icons screenshot](_images/font-awesome-icons.jpg "Font Awesome Icons")](_media/font-awesome-icons-support.pdf)

```
<i style="font-size:50px;color:orange;" class="fa">&#xf1c1;</i>
```

Important: Since version 1.9.6, you must create a new font folder and upload a .ttf or .woff file of Fontawesome and add it in with filters, [more info here.](https://web.archive.org/web/20191016221531/http://wp.dinamiko.com/demos/dkpdf/doc/how-to-add-custom-fonts/)


## Tutorial PDF Advanced Custom Fields

In this tutorial we are going to add Advanced Custom Fields to PDF using DK PDF. We’ll add Text, Image and Google Maps ACF fields to the PDF.

For this tutorial I’ve created a child theme based on Twenty Seventeen and I uploaded it to [GitHub](https://github.com/Dinamiko/dkpdf-acf-tutorial).

### Steps
1. Add dkpdf-index.php template to your Theme (or Child theme)
2. Display ACF fields in the PDF

### 1. Add dkpdf-index.php template to your Theme
Create a new folder `dkpdf` in the root of your Theme (or Child theme).
Inside `dkpdf` folder create a new php file and save it as `dkpdf-index.php`.
Copy template code from here: [`dkpdf-index.php`](https://gist.github.com/Dinamiko/8bf97a3962f140ef34752477116ae4f4) and paste it inside of your `dkpdf-index.php`.

![ACF Theme screenshot](_images/acf-theme.jpg "ACF Theme")

### 2. Display ACF fields in the PDF

![Advanced Custom Fields Post Group screenshot](_images/acf-dkpdf.jpg "Advanced Custom Fields Post Group")

After creating and displaying ACF fields in [`single.php`](https://github.com/Dinamiko/dkpdf-acf-tutorial/blob/master/single.php) template, this is how our post looks like.

![Single post with ACF custom fields screenshot](_images/acf-single-post.jpg "Single post with ACF custom fields")

At this point, if you click PDF Button nothing is going to be printed in the PDF because by default `dkpdf-index.php` template only outputs [`the_content`](https://developer.wordpress.org/reference/functions/the_content/). In order to display ACF fields in the PDF you have to copy ACF code from single.php and paste it to `dkpdf-index.php`.

Open `single.php` and copy the code responsible of displaying the fields:

```
<?php
if( get_field('post_name') ) { ?>
	<p><?php echo get_field('post_name');?></p>
<?php }
$image = get_field('post_image');
if( !empty($image) ): ?>
	<img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />
<?php endif; ?>

<?php
$location = get_field('post_map');
if( !empty($location) ): ?>
	<div class="acf-map">
		<div class="marker" data-lat="<?php echo $location['lat']; ?>" data-lng="<?php echo $location['lng']; ?>"></div>
	</div>
<?php endif; ?>
```

Open `dkpdf-index.php` and paste it in this block of code:

```
<?php
  // add your stuff here
  the_content();
?>
```

If you click PDF Button now, you’ll see Text and Image but not Google Maps, this is because we need an image version of the map. Google Maps implements a function [staticmap](https://developers.google.com/maps/documentation/static-maps/) that returns a map based on its Latitude and Longitude, replace map code with this one:

```
<?php
$location = get_field('post_map');
if( !empty($location) ): ?>
  <img style="margin-top:25px;" width="1500" height="250" src="http://maps.googleapis.com/maps/api/staticmap?center=<?php echo $location['lat'];?>,<?php echo $location['lng'];?>&zoom=15&size=1500x250&sensor=false&markers=color:red%7Clabel:%7C<?php echo $location['lat'];?>,<?php echo $location['lng'];?>">        
<?php endif; ?>
```

Now if you click PDF button you’ll see all ACF fields printed in the PDF:

![PDF with ACF Text, Image and Google Maps fields. screenshot](_images/acf-pdf.jpg "PDF with ACF Text, Image and Google Maps fields.")

That’s all, I hope that you’ve enjoyed the tutorial 🙂


## Tutorial PDF WooCommerce



# Shortcodes

## dkpdf-button

You can add `dkpdf-button` shortcode to any post, page or custom post type.

Use Position / Use shortcode when you’re adding the shortcode button manually either in the content of the post or in a PHP template.

![Use Shortcode screenshot](_images/use-shortcode.jpg "Use Shortcode")

To add the shortcode in a PHP template, use:

```
<?php echo do_shortcode('[dkpdf-button]'); ?>
```

## dkpdf-columns

`Since: 1.9.1`
Allows split the content of the PDF in columns.

### Basic Usage
```
[dkpdf-columns]
Your content goes here....
[/dkpdf-columns]
```

### Shortcode Parameters

`[dkpdf-columns columns="2" equal-columns="false" gap="10"]`

* columns: 2
* equal-columns: false
* gap: 10

### [dkpdf-columnbreak]

Use this shortcode inside dkpdf-columns to start a new column after it, in the example below we are adding dkpdf-columnbreak just after the list:

### Split content in columns

```
[dkpdf-columns]
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin convallis quam sit amet erat egestas mattis. Vestibulum eros dui, bibendum non ante non, placerat placerat nibh.
<ul>
<li>Pellentesque laoreet arcu lorem</li>
<li>At sagittis leo suscipit eu</li>
<li>Nam egestas lorem ornare</li>
<li>Class aptent taciti sociosqu</li>
<li>Ad litora torquent per conubia</li>
<li>Nostra, per inceptos himenaeos.</li>
</ul>
[dkpdf-columnbreak]
Vestibulum risus quis, efficitur libero. Morbi ac mattis odio, ut volutpat est. Nulla faucibus est vel turpis lobortis volutpat. Integer tincidunt feugiat tortor ut eleifend. Cras vitae enim elementum, sagittis lorem dignissim, pharetra nulla. Vivamus placerat dignissim metus sit amet vulputate. Vestibulum pellentesque in dolor non luctus.
[/dkpdf-columns]

[dkpdf-columns columns="3" equal-columns="true" gap="20"]
Etiam sed euismod neque. Cras tristique massa ante, a tincidunt ipsum sagittis vel. Fusce tristique facilisis neque non semper. Vivamus pharetra risus vitae velit ultricies auctor. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Aliquam condimentum felis arcu, eget mollis ipsum pharetra nec. Aliquam justo sapien, fringilla a erat et, luctus elementum nibh. Curabitur tincidunt gravida eleifend. Vivamus ornare auctor lacus, in eleifend ex gravida ac. Quisque sodales dui odio, nec venenatis neque ultrices eget. Phasellus et sodales lectus. Sed quis cursus augue. Maecenas ornare eros dolor, interdum laoreet massa tristique in.
[/dkpdf-columns]
```

## dkpdf-pagebreak

`Since: 1.4`
This shortcode allows adding page breaks in the content. All the content that follows this shortcode is going to go the next page.
You can use this shortcode multiple times in the content.

![Using [dkpdf-pagebreak] screenshot](_images/pagebreak-shortcode.jpg "Using [dkpdf-pagebreak]")

![[dkpdf-pagebreak] results screenshot](_images/pagebreak-shortcode-results.jpg "[dkpdf-pagebreak] results")

## dkpdf-remove
Use this shortcode to remove pieces of content in the generated PDF.

New shortcode `tag` attribute `Since: 1.9`

```
[dkpdf-remove tag="gallery"]
[gallery ids="827,811,770"]
[/dkpdf-remove]
```

![WP Gallery screenshot](_images/wp-gallery.jpg "WP Gallery")

In this example, clicking PDF Button, gallery shortcode isn’t shown in the generated PDF.

[![Download PDF button](_images/download-pdf-button.jpg "Download PDF")](_media/dkpdf-remove.pdf)

![Adding dkpdf-remove shortcode screenshot](_images/remove-shortcode.jpg "Adding dkpdf-remove shortcode")

![dkpdf-remove shortcode screenshot](_images/remove-shortcode-02.jpg "The piece of content inside the shortcode remains visible in the page")

![dkpdf-remove shortcode screenshot](_images/remove-shortcode-03.jpg "The piece of content inside the shortcode is hidden in the pdf")

## Remove shortcodes in PDF

Removes a shortcode by tag (shortcode slug name) in the generated PDF, in this example we’re removing a shortcode with slug: responsivevoice.

```
<?php
// removes specific shortcode in PDF
function dkpdf_remove_shortcodes_bytag( $content ) {
	$pdf = get_query_var( 'pdf' );
	if( $pdf ) {
		remove_shortcode( 'responsivevoice' );
		add_shortcode('responsivevoice', '__return_false');		
	}
	return $content;
}
add_filter( 'the_content', 'dkpdf_remove_shortcodes_bytag');
```

Removes all shortcodes in the generated PDF.

```
<?php 
// removes all shortcodes in PDF
function dkpdf_remove_all_shortcodes( $content ) {
	$pdf = get_query_var( 'pdf' );
	if( $pdf ) {
		return strip_shortcodes( $content );
	} else {
		return $content;
	}
}
add_filter( 'the_content', 'dkpdf_remove_all_shortcodes' );
```

# Developers

## Filters

### dkpdf_pdf_filename `Since: 1.9.1`

Example adding Author name to PDF filename.

```
<?php
function custom_dkpdf_pdf_filename( $filename ) {
  $current_user = wp_get_current_user();
  $filename = $current_user->display_name.' '.get_the_title();
  return $filename;
}
add_filter( 'dkpdf_pdf_filename', 'custom_dkpdf_pdf_filename' );
```

### dkpdf_pdf_author `Since: 1.9.1`

Allows set up PDF Author metadata.

```
<?php
function custom_dkpdf_pdf_author( $author ) {
  $author = 'your-logic-here';
  return $author;
}
add_filter( 'dkpdf_pdf_author',  'custom_dkpdf_pdf_author' );
```

### dkpdf_header_title `Since: 1.8`

Example adding author after post title in PDF header.

```
<?php
function changing_dkpdf_header_title( $title ) {
	if ( have_posts() ) : while ( have_posts() ) : the_post();
		$title .= ' by ' . get_the_author_meta( 'display_name' );
	endwhile; else:
	endif;
	return $title;
}
add_filter( 'dkpdf_header_title', 'changing_dkpdf_header_title' );
```

### dkpdf_pdf_format `Since: 1.8`

Example changing PDF format to Royal

```
<?php
function changing_dkpdf_pdf_format( $format ) {
	$format = 'Royal';
	return $format;
}
add_filter( 'dkpdf_pdf_format', 'changing_dkpdf_pdf_format' );
```

Available formats:
* A0 – A10, B0 – B10, C0 – C10
* 4A0, 2A0, RA0 – RA4, SRA0 – SRA4
* Letter, Legal, Executive, Folio
* Demy, Royal
* A (Type A paperback 111x178mm)
* B (Type B paperback 128x198mm)

### dkpdf_before_content and dkpdf_after_content `Since: 1.7`

Example adding custom content before and after PDF content.

```
<?php
function custom_dkpdf_before_content() {
	$output = '<div style="margin-bottom:25px;width:100%;background:#CCC;">Before content text</div>';
	return $output;
}
add_filter( 'dkpdf_before_content', 'custom_dkpdf_before_content' );
function custom_dkpdf_after_content() {
	$output = '<div style="margin-top:25px;width:100%;background:#CCC;">After content text</div>';
	return $output;
}
add_filter( 'dkpdf_after_content', 'custom_dkpdf_after_content' );
```

### custom_dkpdf_button_container_css `Since: 1.7`

Example changing .dkpdf-button-container css.

```
<?php 
function custom_dkpdf_button_container_css() {
	return 'float:none;';
}
add_filter( 'dkpdf_button_container_css', 'custom_dkpdf_button_container_css' );
```

### dkpdf_header_pagination and dkpdf_footer_pagination `Since: 1.4`

Example changing default pagination content in header and footer.

```

<?php
// Example changing default header pagination content
function changing_dkpdf_header_pagination( $content ) {
	$content = 'Page {PAGENO} of {nb}';
	return $content;
}
add_filter( 'dkpdf_header_pagination', 'changing_dkpdf_header_pagination' );
// Example changing default footer pagination content
function changing_dkpdf_footer_pagination( $content ) {
	$content = 'Page {PAGENO} of {nb}';
	return $content;
}
add_filter( 'dkpdf_footer_pagination', 'changing_dkpdf_footer_pagination' );
```

### dkpdf_query_args

Example changing wp query arguments for the generated PDF:

```
<?php
/**
* Example changing wp query arguments for the generated PDF
* $args = array( 'p' => $pdf, 'post_type' => $post_type, 'post_status' => 'publish' ); 
*/
function changing_dkpdf_query_args( $args ) {
	$args['p'] = 1710;
	$args['post_type'] = 'doc';
	return $args;
}
add_filter( 'dkpdf_query_args', 'changing_dkpdf_query_args' );
?>
```

### dkpdf_posts_arr

Example removing post type attachment in PDF Button Post types to apply:

```
<?php 
/**
* Removes post type attachment in PDF Button Post types to apply
*/
function remove_dkpdf_posts_arr( $post_arr ) {
	unset( $post_arr['attachment'] );
	return $post_arr;
}
add_filter( 'dkpdf_posts_arr', 'remove_dkpdf_posts_arr' );
?>
```

Example adding post type nav_menu_item in PDF Button Post types to apply:

```
<?php
/**
* Adds post type nav_menu_item in PDF Button Post types to apply
*/
function add_dkpdf_posts_arr( $post_arr ) {
	array_push( $post_arr, 'nav_menu_item' );
}
add_filter( 'dkpdf_posts_arr', 'add_dkpdf_posts_arr' );
?>
```

### dkpdf_hide_button_isset and dkpdf_hide_button_equal `Since: 1.7`

Example adding dkpdfg_action_create_categories $_POST (used in DK PDF Generator select categories)

```
<?php
function dkpdfg_hide_button_isset() {
	return isset( $_POST['dkpdfg_action_create'] ) || isset( $_POST['dkpdfg_action_create_categories'] );
}
add_filter( 'dkpdf_hide_button_isset', 'dkpdfg_hide_button_isset' );
function dkpdfg_hide_button_equal() {
	return $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' || $_POST['dkpdfg_action_create_categories'] == 'dkpdfg_action_create_categories';
}
add_filter( 'dkpdf_hide_button_equal', 'dkpdfg_hide_button_equal' );
```

# Changelog

* 1.9.7
 * Fixed plugin text domain and some strings without translation functions

* 1.9.6
 * Update mPDF library to latest version.
 * New filters dkpdf_mpdf_font_dir, dkpdf_mpdf_font_data, dkpdf_mpdf_temp_dir. Thanks to joostvanbockel.

* 1.9.3
 * Reverting to 1.9.1, something went wrong in 1.9.2

* 1.9.2
 * PHP7: Remove some warnings, see issue #38, issue #48.
 * HTTPS: Fix images not working after move to https, see issue #51.

* 1.9.1
 * Added PDF Protection in PDF Setup Settings
 * New Columns Shortcodes: [dkpdf-columns] and [dkpdf-columnbreak]
 * New Filter: dkpdf_pdf_filename
 * Fixed Admin scripts enqueued on all pages (thanks to Aristeides Stathopoulos @aristath)

* 1.9
 * Added shortcode tag attribute to dkpdf-remove shortcode
 * FontAwesome icons support
 * Added post title as PDF filename when downloaded from browser

* 1.8
 * New filter dkpdf_pdf_format
 * New filter dkpdf_header_title
 * Option for remove default PDF button when adding PDF button manually (thanks to Renato Alves)

* 1.7
 * New filters (see documentation filters)
 * Fixed github issues #21 #23 #24

* 1.6
 * 4.4.2 Tested
 * Added DK PDF Generator compatibility (css + shortcodes)

* 1.5
 * Added PDF Custom CSS setting
 * Sanitized settings fields

* 1.4
 * Added [dkpdf-pagebreak] shortcode for adding page breaks
 * Added filters dkpdf_header_pagination and dkpdf_footer_pagination
 * Added addons page to admin menu

* 1.3
 * New DK PDF admin menu for better usability
 * Added a PDF Setup tab for adjusting page orientation, font size and margins of the PDF
 * Added [dkpdf-remove] shortcode for removing pieces of content in the generated PDF

* 1.2
 * Settings link in plugins list page
 * Adjusts header template for better logo display

* 1.1
 * Removes dkpdf-button shortcode in the generated PDF

* 1.0
 * Initial release

# Credits

* All documentation was written by the plugin author [Emili Castells](https://www.dinamiko.com/).
* [Yordan Soares](https://yordansoar.es/) worked in the new documentation layout using [docsify](https://docsify.js.org/).
* **DK PDF** use the [mPDF](https://mpdf.github.io/) PHP library which generates PDF files from UTF-8 encoded HTML.
* **DK PDF** use [Font Awesome](https://fontawesome.com/), the iconic font and CSS toolkit.