# Filters

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