<?php
/**
* dkpdf-button-archive.php
* This template is used to display DK PDF Button for archive pages
*
* Do not edit this template directly,
* copy this template and paste in your theme inside a directory named dkpdf
*/

// Check if we're using polylang plugin
if( function_exists( 'pll_register_string' )  ) {
	// Get button text setting value from polylang
	$pdfbutton_text = pll__( 'PDF Button' );
} else {
	$pdfbutton_text = sanitize_option( 'dkpdf_pdfbutton_text', get_option( 'dkpdf_pdfbutton_text', 'PDF Button' ) );
}

$pdfbutton_align = sanitize_option( 'dkpdf_pdfbutton_align', get_option( 'dkpdf_pdfbutton_align', 'right' ) );

// Determine the correct PDF parameter for archive context
$pdf_param = '';

if ( function_exists( 'is_shop' ) && is_shop() ) {
	// WooCommerce shop page
	$pdf_param = 'shop';
} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
	// Product category archive
	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Term ) {
		$pdf_param = 'product_cat_' . $queried_object->term_id;
	}
} elseif ( function_exists( 'is_product_tag' ) && is_product_tag() ) {
	// Product tag archive
	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Term ) {
		$pdf_param = 'product_tag_' . $queried_object->term_id;
	}
} elseif ( is_category() ) {
	// Regular category archive
	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Term ) {
		$pdf_param = 'category_' . $queried_object->term_id;
	}
} elseif ( is_tag() ) {
	// Regular tag archive
	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Term ) {
		$pdf_param = 'tag_' . $queried_object->term_id;
	}
} else {
	// Fallback for other archive types
	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Term ) {
		$pdf_param = $queried_object->taxonomy . '_' . $queried_object->term_id;
	} elseif ( $queried_object instanceof WP_Post ) {
		$pdf_param = $queried_object->ID;
	}
}

// Only show button if we have a valid PDF parameter
if ( ! empty( $pdf_param ) ) : ?>

<div class="dkpdf-button-container" style="<?php
    $container_css = apply_filters('dkpdf_button_container_css', '');
    echo esc_attr($container_css);?>
        text-align:<?php echo esc_attr($pdfbutton_align);?> ">

	<a class="dkpdf-button" href="<?php echo esc_url( add_query_arg( 'pdf', $pdf_param ) );?>" target="_blank"><span class="dkpdf-button-icon"><i class="fa fa-file-pdf-o"></i></span> <?php echo wp_kses_post($pdfbutton_text);?></a>

</div>

<?php endif; ?>