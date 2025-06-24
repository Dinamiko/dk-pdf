<?php

use Dinamiko\DKPDF\Vendor\Mpdf\Config\ConfigVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Config\FontVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Mpdf;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays PDF button based on settings and context
 */
function dkpdf_display_pdf_button( $content ) {
	$pdf = isset( $_GET['pdf'] ) ? sanitize_text_field( $_GET['pdf'] ) : '';

	// Don't display button in PDF view or during form submission
	if ( ( isset( $_POST['dkpdfg_action_create'] ) &&
	       ( $_POST['dkpdfg_action_create'] === 'dkpdfg_action_create' || $pdf ) ) || $pdf ) {
		remove_shortcode( 'dkpdf-button' );

		return str_replace( "[dkpdf-button]", "", $content );
	}

	// Get settings
	$option_post_types = get_option( 'dkpdf_pdfbutton_post_types', [] );

	// Check if button should be shown based on current context
	if ( is_singular() && ! empty( $option_post_types ) ) {
		global $post;
		if ( ! in_array( get_post_type( $post ), $option_post_types ) || get_post_type( $post ) === 'product' ) {
			return $content;
		}
	} else {
		// Not a singular post type, return content without button
		return $content;
	}

	// Get button position setting
	$pdfbutton_position = get_option( 'dkpdf_pdfbutton_position', 'before' );
	$template           = new DKPDF_Template_Loader;

	// Return content if using shortcode
	if ( $pdfbutton_position == 'shortcode' ) {
		return $content;
	}

	// Add button before or after content
	ob_start();
	$template->get_template_part( 'dkpdf-button' );
	$button = ob_get_clean();

	if ( $pdfbutton_position == 'before' ) {
		return $button . $content;
	} elseif ( $pdfbutton_position == 'after' ) {
		return $content . $button;
	}

	return $content;
}

add_filter( 'the_content', 'dkpdf_display_pdf_button' );

/**
 * Outputs the PDF when requested
 */
function dkpdf_output_pdf( $query ) {
	$pdf = isset( $_GET['pdf'] ) ? sanitize_text_field( $_GET['pdf'] ) : '';
	if ( ! $pdf || ! is_numeric( $pdf ) ) {
		return;
	}

	// For debugging - output HTML only if admin and output=html param is provided
	$output = isset( $_GET['output'] ) ? sanitize_text_field( $_GET['output'] ) : '';
	if ( $output === 'html' && current_user_can( 'manage_options' ) ) {
		$template_content = dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) );
		echo preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $template_content );
		exit;
	}

	require_once realpath( __DIR__ . '/..' ) . '/vendor/autoload.php';

	// Configure PDF options from settings
	$config = [
		'tempDir'           => apply_filters( 'dkpdf_mpdf_temp_dir', realpath( __DIR__ . '/..' ) . '/tmp' ),
		'default_font_size' => get_option( 'dkpdf_font_size', '12' ),
		'format'            => get_option( 'dkpdf_page_orientation' ) == 'horizontal' ?
			apply_filters( 'dkpdf_pdf_format', 'A4' ) . '-L' :
			apply_filters( 'dkpdf_pdf_format', 'A4' ),
		'margin_left'       => get_option( 'dkpdf_margin_left', '15' ),
		'margin_right'      => get_option( 'dkpdf_margin_right', '15' ),
		'margin_top'        => get_option( 'dkpdf_margin_top', '50' ),
		'margin_bottom'     => get_option( 'dkpdf_margin_bottom', '30' ),
		'margin_header'     => get_option( 'dkpdf_margin_header', '15' ),
	];

	// Add font configuration
	$default_config      = ( new ConfigVariables() )->getDefaults();
	$default_font_config = ( new FontVariables() )->getDefaults();

	$config['fontDir']  = apply_filters( 'dkpdf_mpdf_font_dir', $default_config['fontDir'] );
	$config['fontdata'] = apply_filters( 'dkpdf_mpdf_font_data', $default_font_config['fontdata'] );

	// Apply final config filter
	$mpdf_config = apply_filters( 'dkpdf_mpdf_config', $config );

	// Create PDF instance
	$mpdf = new Mpdf( $mpdf_config );

	// Set protection if enabled
	if ( get_option( 'dkpdf_enable_protection' ) == 'on' ) {
		$mpdf->SetProtection( get_option( 'dkpdf_grant_permissions', [] ) );
	}

	// Enable column mode if configured
	if ( get_option( 'dkpdf_keep_columns' ) == 'on' ) {
		$mpdf->keepColumns = true;
	}

	// Set header and footer
	$mpdf->SetHTMLHeader( dkpdf_get_template( 'dkpdf-header' ) );
	$mpdf->SetHTMLFooter( dkpdf_get_template( 'dkpdf-footer' ) );

	// Write content
	$mpdf->WriteHTML( apply_filters( 'dkpdf_before_content', '' ) );
	$mpdf->WriteHTML( dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) ) );
	$mpdf->WriteHTML( apply_filters( 'dkpdf_after_content', '' ) );

	// Set document properties
	global $post;
	$title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );
	$mpdf->SetTitle( $title );
	$mpdf->SetAuthor( apply_filters( 'dkpdf_pdf_author', get_bloginfo( 'name' ) ) );

	// Output PDF (open in browser or download)
	$action = get_option( 'dkpdf_pdfbutton_action', 'open' ) == 'open' ? 'I' : 'D';
	$mpdf->Output( $title . '.pdf', $action );
	exit;
}

add_action( 'wp', 'dkpdf_output_pdf' );

/**
 * Returns rendered template content
 */
function dkpdf_get_template( $template_name ) {
	$template = new DKPDF_Template_Loader;
	ob_start();
	$template->get_template_part( get_option( 'dkpdf_selected_template', '' ) . $template_name );

	return ob_get_clean();
}

/**
 * Returns array of post types
 */
function dkpdf_get_post_types() {
	$custom_types = get_post_types( [ 'public' => true, '_builtin' => false ] );
	$post_arr     = [ 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' ];

	foreach ( $custom_types as $post_type ) {
		$post_arr[ $post_type ] = $post_type;
	}

	return apply_filters( 'dkpdf_posts_arr', $post_arr );
}

/**
 * Returns array of taxonomies
 */
function dkpdf_get_taxonomies() {
	$custom_taxonomies  = get_taxonomies( [ 'public' => true, '_builtin' => false ] );
	$builtin_taxonomies = get_taxonomies( [ 'public' => true, '_builtin' => true ] );
	$all_taxonomies     = array_merge( $custom_taxonomies, $builtin_taxonomies );

	$tax_arr = [];
	foreach ( $all_taxonomies as $taxonomy ) {
		$tax_arr[ $taxonomy ] = $taxonomy;
	}

	unset( $tax_arr['post_format'] );

	unset( $tax_arr['product_shipping_class'] );
	unset( $tax_arr['product_brand'] );

	return apply_filters( 'dkpdf_taxonomies_arr', $tax_arr );
}

/**
 * Register settings sanitization functions
 */
function dkpdf_sanitize_options() {
	$text_fields = [
		'pdfbutton_text',
		'pdfbutton_action',
		'pdfbutton_position',
		'pdfbutton_align',
		'page_orientation',
		'pdf_header_image',
		'pdf_header_show_title',
		'pdf_header_show_pagination',
		'pdf_footer_show_title',
		'pdf_footer_show_pagination',
		'print_wp_head'
	];

	$int_fields = [
		'font_size',
		'margin_left',
		'margin_right',
		'margin_top',
		'margin_bottom',
		'margin_header'
	];

	// Add text field sanitization
	foreach ( $text_fields as $field ) {
		add_filter( "pre_update_option_dkpdf_{$field}", function ( $new_value ) {
			return sanitize_text_field( $new_value );
		}, 10, 2 );
	}

	// Add integer field sanitization
	foreach ( $int_fields as $field ) {
		add_filter( "pre_update_option_dkpdf_{$field}", function ( $new_value ) {
			return intval( $new_value );
		}, 10, 2 );
	}

	// Special case for footer text with allowed HTML
	add_filter( 'pre_update_option_dkpdf_pdf_footer_text', 'dkpdf_update_field_dkpdf_pdf_footer_text', 10, 2 );

	// Special case for CSS
	add_filter( 'pre_update_option_dkpdf_pdf_custom_css', 'dkpdf_update_field_dkpdf_pdf_custom_css', 10, 2 );

	// Safely sanitize array fields
	add_filter( 'pre_update_option_dkpdf_pdfbutton_post_types', function ( $new_value ) {
		return is_array( $new_value ) ? $new_value : [];
	}, 10, 2 );
}

add_action( 'init', 'dkpdf_sanitize_options' );

/**
 * Sanitizes footer text allowing limited HTML tags
 */
function dkpdf_update_field_dkpdf_pdf_footer_text( $new_value, $old_value ) {
	$allowed_html = [
		'a'      => [ 'href' => [], 'title' => [], 'class' => [], 'style' => [] ],
		'br'     => [],
		'em'     => [],
		'strong' => [],
		'hr'     => [],
		'p'      => [ 'title' => [], 'class' => [], 'style' => [] ],
		'h1'     => [ 'title' => [], 'class' => [], 'style' => [] ],
		'h2'     => [ 'title' => [], 'class' => [], 'style' => [] ],
		'h3'     => [ 'title' => [], 'class' => [], 'style' => [] ],
		'h4'     => [ 'title' => [], 'class' => [], 'style' => [] ],
		'div'    => [ 'title' => [], 'class' => [], 'style' => [] ]
	];

	return wp_kses( $new_value, $allowed_html );
}

/**
 * Sanitizes custom CSS
 */
function dkpdf_update_field_dkpdf_pdf_custom_css( $new_value, $old_value ) {
	$new_value = wp_filter_nohtml_kses( $new_value );
	$new_value = str_replace( '\"', '"', $new_value );
	$new_value = str_replace( "\'", "'", $new_value );

	return $new_value;
}

/**
 * Determine which template to use based on page type
 */
add_filter( 'dkpdf_content_template', function ( $template ) {
	// If no template set is selected, return legacy template
	if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
		return $template;
	}

	if (class_exists('WooCommerce')) {
		// Check for single product page
		if ( is_product() ) {
			return 'dkpdf-single-product';
		}

		// Check for shop page or product archive
		if ( is_shop() || is_product_category() || is_product_tag() ) {
			return 'dkpdf-archive-product';
		}
	}

	if ( is_single() ) {
		return 'dkpdf-single';
	}

	if ( is_archive() || is_home() || is_front_page() ) {
		return 'dkpdf-archive';
	}

	return $template;
} );

/**
 * Add PDF button to archive descriptions if applicable
 */
add_filter( 'get_the_archive_description', function ( $description ) {
	if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
		return $description;
	}

	$option_taxonomies = get_option( 'dkpdf_pdfbutton_taxonomies', [] );
	$queried_object    = get_queried_object();
	if ( $queried_object instanceof WP_Term && ! empty( $option_taxonomies ) ) {
		if ( in_array( $queried_object->taxonomy, $option_taxonomies ) ) {
			ob_start();
			( new DKPDF_Template_Loader() )->get_template_part( 'dkpdf-button' );
			$button = ob_get_clean();

			return $description . $button;
		}
	}

	return $description;

} );

/**
 * Adds a PDF button to the shop category page before the products list.
 */
add_action( 'woocommerce_before_shop_loop', function () {
	ob_start();
	( new DKPDF_Template_Loader() )->get_template_part( 'dkpdf-button' );
	echo ob_get_clean();
} );

/**
 * Adds a PDF button to the single product page.
 */
add_action( 'woocommerce_product_meta_start', function () {
	$option_post_types = get_option( 'dkpdf_pdfbutton_post_types', [] );
	global $post;
	if ( ! in_array( get_post_type( $post ), $option_post_types ) ) {
		return;
	}

	ob_start();
	( new DKPDF_Template_Loader() )->get_template_part( 'dkpdf-button' );
	echo ob_get_clean();
} );

