<?php

use Dinamiko\DKPDF\Vendor\Mpdf\Config\ConfigVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Config\FontVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Mpdf;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * displays pdf button
 */
function dkpdf_display_pdf_button( $content ) {
	$pdf = get_query_var( 'pdf' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( apply_filters( 'dkpdf_hide_button_isset', isset( $_POST['dkpdfg_action_create'] ) ) ) {
		if ( $pdf || apply_filters( 'dkpdf_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' ) ) {
			// phpcs:enable
			remove_shortcode( 'dkpdf-button' );
			$content = str_replace( "[dkpdf-button]", "", $content );

			return $content;
		}
	} else {
		if ( $pdf ) {
			remove_shortcode( 'dkpdf-button' );
			$content = str_replace( "[dkpdf-button]", "", $content );

			return $content;
		}
	}

	$option_post_types = sanitize_option( 'dkpdf_pdfbutton_post_types', get_option( 'dkpdf_pdfbutton_post_types', array() ) );
	$option_taxonomies = sanitize_option( 'dkpdf_pdfbutton_taxonomies', get_option( 'dkpdf_pdfbutton_taxonomies', array() ) );
	$show_button       = false;

	if ( is_singular() && ! empty( $option_post_types ) ) {
		global $post;
		$show_button = in_array( get_post_type( $post ), $option_post_types );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
			return $content;
		}

		$queried_object = get_queried_object();
		if ( $queried_object instanceof WP_Term && ! empty( $option_taxonomies ) ) {
			$show_button = in_array( $queried_object->taxonomy, $option_taxonomies );
		}
	}

	if ( ! $show_button ) {
		return $content;
	}

	$c                  = $content;
	$pdfbutton_position = sanitize_option( 'dkpdf_pdfbutton_position', get_option( 'dkpdf_pdfbutton_position', 'before' ) );
	$template           = new DKPDF_Template_Loader;

	if ( $pdfbutton_position == 'shortcode' ) {
		return $c;
	}

	if ( $pdfbutton_position == 'before' ) {
		ob_start();
		$template->get_template_part( 'dkpdf-button' );

		return ob_get_clean() . $c;
	} elseif ( $pdfbutton_position == 'after' ) {
		ob_start();
		$template->get_template_part( 'dkpdf-button' );

		return $c . ob_get_clean();
	}

	return $content;
}

add_filter( 'the_content', 'dkpdf_display_pdf_button' );

/**
 * output the pdf
 */
function dkpdf_output_pdf( $query ) {
	$pdf = sanitize_text_field( wp_unslash( $_GET['pdf'] ?? '' ) );
	if ( ! $pdf || ! is_numeric( $pdf ) ) {
		return;
	}

	$output = sanitize_text_field( wp_unslash( $_GET['output'] ?? '' ) );
	if ( $output === 'html' && current_user_can( 'manage_options' ) ) {
		$template_content = dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) );

		// Remove all script tags and their contents so we get only HTML and CSS.
		$template_content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $template_content );

		echo $template_content;
		exit;
	}

	require_once realpath( __DIR__ . '/..' ) . '/vendor/autoload.php';

	// page orientation
	$dkpdf_page_orientation = get_option( 'dkpdf_page_orientation', '' );

	if ( $dkpdf_page_orientation == 'horizontal' ) {

		$format = apply_filters( 'dkpdf_pdf_format', 'A4' ) . '-L';

	} else {

		$format = apply_filters( 'dkpdf_pdf_format', 'A4' );

	}

	// font size
	$dkpdf_font_size = get_option( 'dkpdf_font_size', '12' );

	// margins
	$dkpdf_margin_left   = get_option( 'dkpdf_margin_left', '15' );
	$dkpdf_margin_right  = get_option( 'dkpdf_margin_right', '15' );
	$dkpdf_margin_top    = get_option( 'dkpdf_margin_top', '50' );
	$dkpdf_margin_bottom = get_option( 'dkpdf_margin_bottom', '30' );
	$dkpdf_margin_header = get_option( 'dkpdf_margin_header', '15' );

	// fonts
	$mpdf_default_config = ( new ConfigVariables() )->getDefaults();
	$dkpdf_mpdf_font_dir = apply_filters( 'dkpdf_mpdf_font_dir', $mpdf_default_config['fontDir'] );

	$mpdf_default_font_config = ( new FontVariables() )->getDefaults();
	$dkpdf_mpdf_font_data     = apply_filters( 'dkpdf_mpdf_font_data', $mpdf_default_font_config['fontdata'] );

	// temp directory
	$dkpdf_mpdf_temp_dir = apply_filters( 'dkpdf_mpdf_temp_dir', realpath( __DIR__ . '/..' ) . '/tmp' );

	$mpdf_config = apply_filters( 'dkpdf_mpdf_config', [
		'tempDir'           => $dkpdf_mpdf_temp_dir,
		'default_font_size' => $dkpdf_font_size,
		'format'            => $format,
		'margin_left'       => $dkpdf_margin_left,
		'margin_right'      => $dkpdf_margin_right,
		'margin_top'        => $dkpdf_margin_top,
		'margin_bottom'     => $dkpdf_margin_bottom,
		'margin_header'     => $dkpdf_margin_header,
		'fontDir'           => $dkpdf_mpdf_font_dir,
		'fontdata'          => $dkpdf_mpdf_font_data,
	] );

	// creating and setting the pdf
	$mpdf = new Mpdf( $mpdf_config );

	// encrypts and sets the PDF document permissions
	// https://mpdf.github.io/reference/mpdf-functions/setprotection.html
	$enable_protection = get_option( 'dkpdf_enable_protection' );

	if ( $enable_protection == 'on' ) {
		$grant_permissions = get_option( 'dkpdf_grant_permissions' );
		$mpdf->SetProtection( $grant_permissions );
	}

	// keep columns
	$keep_columns = get_option( 'dkpdf_keep_columns' );

	if ( $keep_columns == 'on' ) {
		$mpdf->keepColumns = true;
	}

	/*
	// make chinese characters work in the pdf
	$mpdf->useAdobeCJK = true;
	$mpdf->autoScriptToLang = true;
	$mpdf->autoLangToFont = true;
	*/

	// header
	$pdf_header_html = dkpdf_get_template( 'dkpdf-header' );
	$mpdf->SetHTMLHeader( $pdf_header_html );

	// footer
	$pdf_footer_html = dkpdf_get_template( 'dkpdf-footer' );
	$mpdf->SetHTMLFooter( $pdf_footer_html );

	$mpdf->WriteHTML( apply_filters( 'dkpdf_before_content', '' ) );

	$mpdf->WriteHTML( dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) ) );

	$mpdf->WriteHTML( apply_filters( 'dkpdf_after_content', '' ) );

	// action to do (open or download)
	$pdfbutton_action = sanitize_option( 'dkpdf_pdfbutton_action', get_option( 'dkpdf_pdfbutton_action', 'open' ) );

	global $post;
	$title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );

	$mpdf->SetTitle( $title );
	$mpdf->SetAuthor( apply_filters( 'dkpdf_pdf_author', get_bloginfo( 'name' ) ) );

	if ( $pdfbutton_action == 'open' ) {

		$mpdf->Output( $title . '.pdf', 'I' );

	} else {

		$mpdf->Output( $title . '.pdf', 'D' );

	}

	exit;
}

add_action( 'wp', 'dkpdf_output_pdf' );

/**
 * Returns a template
 *
 * @param string template name
 */
function dkpdf_get_template( $template_name ) {
	$template = new DKPDF_Template_Loader;

	ob_start();
	$template->get_template_part( get_option( 'dkpdf_selected_template', '' ) . $template_name );

	return ob_get_clean();

}

/**
 * returns an array of active post, page, attachment and custom post types
 * @return array
 */
function dkpdf_get_post_types() {

	$args = array(
		'public'   => true,
		'_builtin' => false
	);

	$post_types = get_post_types( $args );
	$post_arr   = array( 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' );

	foreach ( $post_types as $post_type ) {

		$arr      = array( $post_type => $post_type );
		$post_arr += $arr;

	}

	$post_arr = apply_filters( 'dkpdf' . '_posts_arr', $post_arr );

	return $post_arr;

}

function dkpdf_get_taxonomies() {
	$custom_taxonomies = get_taxonomies( array(
		'public'   => true,
		'_builtin' => false
	) );

	$builtin_taxonomies = get_taxonomies( array(
		'public'   => true,
		'_builtin' => true
	) );

	$all_taxonomies = array_merge( $custom_taxonomies, $builtin_taxonomies );
	$tax_arr        = array();

	foreach ( $all_taxonomies as $taxonomy ) {
		$arr     = array( $taxonomy => $taxonomy );
		$tax_arr += $arr;
	}

	return apply_filters( 'dkpdf_taxonomies_arr', $tax_arr );
}

/**
 * set query_vars
 */
function dkpdf_set_query_vars( $query_vars ) {

	$query_vars[] = 'pdf';

	return $query_vars;

}

add_filter( 'query_vars', 'dkpdf_set_query_vars' );

/**
 * sanitizes dkpdf options
 */
function dkpdf_sanitize_options() {

	add_filter( 'pre_update_option_dkpdf_pdfbutton_text', 'dkpdf_update_field_dkpdf_pdfbutton_text', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdfbutton_post_types', 'dkpdf_update_field_dkpdf_pdfbutton_post_types', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdfbutton_action', 'dkpdf_update_field_dkpdf_pdfbutton_action', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdfbutton_position', 'dkpdf_update_field_dkpdf_pdfbutton_position', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdfbutton_align', 'dkpdf_update_field_dkpdf_pdfbutton_align', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_page_orientation', 'dkpdf_update_field_dkpdf_page_orientation', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_font_size', 'dkpdf_update_field_dkpdf_font_size', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_margin_left', 'dkpdf_update_field_dkpdf_margin_left', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_margin_right', 'dkpdf_update_field_dkpdf_margin_right', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_margin_top', 'dkpdf_update_field_dkpdf_margin_top', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_margin_bottom', 'dkpdf_update_field_dkpdf_margin_bottom', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_margin_header', 'dkpdf_update_field_dkpdf_margin_header', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_header_image', 'dkpdf_update_field_dkpdf_pdf_header_image', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_header_show_title', 'dkpdf_update_field_dkpdf_pdf_header_show_title', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_header_show_pagination', 'dkpdf_update_field_dkpdf_pdf_header_show_pagination', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_footer_text', 'dkpdf_update_field_dkpdf_pdf_footer_text', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_footer_show_title', 'dkpdf_update_field_dkpdf_pdf_footer_show_title', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_footer_show_pagination', 'dkpdf_update_field_dkpdf_pdf_footer_show_pagination', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_pdf_custom_css', 'dkpdf_update_field_dkpdf_pdf_custom_css', 10, 2 );
	add_filter( 'pre_update_option_dkpdf_print_wp_head', 'dkpdf_update_field_dkpdf_print_wp_head', 10, 2 );


}

add_action( 'init', 'dkpdf_sanitize_options' );

/**
 * sanitizes dkpdf_pdfbutton_text option
 */
function dkpdf_update_field_dkpdf_pdfbutton_text( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdfbutton_post_types option
 */
function dkpdf_update_field_dkpdf_pdfbutton_post_types( $new_value, $old_value ) {
	// TODO sanitize_text_field doesn't work
	//$new_value = sanitize_text_field( $new_value );
	return $new_value;
}

/**
 * sanitizes dkpdf_pdfbutton_action option
 */
function dkpdf_update_field_dkpdf_pdfbutton_action( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdfbutton_position option
 */
function dkpdf_update_field_dkpdf_pdfbutton_position( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdfbutton_align option
 */
function dkpdf_update_field_dkpdf_pdfbutton_align( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_page_orientation option
 */
function dkpdf_update_field_dkpdf_page_orientation( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_font_size option
 */
function dkpdf_update_field_dkpdf_font_size( $new_value, $old_value ) {
	$new_value = intval( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_margin_left option
 */
function dkpdf_update_field_dkpdf_margin_left( $new_value, $old_value ) {
	$new_value = intval( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_margin_right option
 */
function dkpdf_update_field_dkpdf_margin_right( $new_value, $old_value ) {
	$new_value = intval( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_margin_top option
 */
function dkpdf_update_field_dkpdf_margin_top( $new_value, $old_value ) {
	$new_value = intval( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_margin_bottom option
 */
function dkpdf_update_field_dkpdf_margin_bottom( $new_value, $old_value ) {
	$new_value = intval( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_margin_header option
 */
function dkpdf_update_field_dkpdf_margin_header( $new_value, $old_value ) {
	$new_value = intval( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdf_header_image option
 */
function dkpdf_update_field_dkpdf_pdf_header_image( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdf_header_show_title option
 */
function dkpdf_update_field_dkpdf_pdf_header_show_title( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdf_header_show_pagination option
 */
function dkpdf_update_field_dkpdf_pdf_header_show_pagination( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdf_footer_text option
 */
function dkpdf_update_field_dkpdf_pdf_footer_text( $new_value, $old_value ) {

	$arr = array(
		'a'      => array(
			'href'  => array(),
			'title' => array(),
			'class' => array(),
			'style' => array()
		),
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'hr'     => array(),
		'p'      => array(
			'title' => array(),
			'class' => array(),
			'style' => array()
		),
		'h1'     => array(
			'title' => array(),
			'class' => array(),
			'style' => array()
		),
		'h2'     => array(
			'title' => array(),
			'class' => array(),
			'style' => array()
		),
		'h3'     => array(
			'title' => array(),
			'class' => array(),
			'style' => array()
		),
		'h4'     => array(
			'title' => array(),
			'class' => array(),
			'style' => array()
		),
		'div'    => array(
			'title' => array(),
			'class' => array(),
			'style' => array()
		)
	);

	$new_value = wp_kses( $new_value, $arr );

	return $new_value;

}

/**
 * sanitizes dkpdf_pdf_header_show_pagination option
 */
function dkpdf_update_field_dkpdf_pdf_footer_show_title( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdf_header_show_pagination option
 */
function dkpdf_update_field_dkpdf_pdf_footer_show_pagination( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_pdf_custom_css option
 */
function dkpdf_update_field_dkpdf_pdf_custom_css( $new_value, $old_value ) {
	$new_value = wp_filter_nohtml_kses( $new_value );
	$new_value = str_replace( '\"', '"', $new_value );
	$new_value = str_replace( "\'", "'", $new_value );

	return $new_value;
}

/**
 * sanitizes dkpdf_print_wp_head option
 */
function dkpdf_update_field_dkpdf_print_wp_head( $new_value, $old_value ) {
	$new_value = sanitize_text_field( $new_value );

	return $new_value;
}

add_filter( 'dkpdf_content_template', function ( $template ) {
	// If no template set is selected in settings, return the legacy template.
	if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
		return $template;
	}

	if ( is_single() ) {
		return 'dkpdf-single';
	}

	if ( is_archive() || is_home() || is_front_page() ) {
		return 'dkpdf-archive';
	}

	return $template;
} );
