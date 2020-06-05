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