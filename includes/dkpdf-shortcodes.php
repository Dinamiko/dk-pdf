<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
* [dkpdf-button]
* This shortcode is used to display DK PDF Button
* doesn't has attributes, uses settings from DK PDF Settings / PDF Button
*/
function dkpdf_button_shortcode( $atts, $content = null ) {

	$template = new DKPDF_Template_Loader;

	ob_start();

	$template->get_template_part( 'dkpdf-button' );

	return ob_get_clean();

}

add_shortcode( 'dkpdf-button', 'dkpdf_button_shortcode' );

/**
* [dkpdf-remove tag="gallery"]content to remove[/dkpdf-remove]
* This shortcode is used remove pieces of content in the generated PDF
* @return string
*/
function dkpdf_remove_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts( array(
			'tag' => ''
		), $atts );

		$tag = sanitize_text_field( $atts['tag'] );
		$pdf = get_query_var( 'pdf' );
		if( $tag !== '' && $pdf ) {
				remove_shortcode( $tag );
				add_shortcode($tag, '__return_false');

				return do_shortcode( $content );
		}

		return do_shortcode( $content );
/*
	$pdf = get_query_var( 'pdf' );

  	if( apply_filters( 'dkpdf_hide_button_isset', isset( $_POST['dkpdfg_action_create'] ) ) ) {
    	if ( $pdf || apply_filters( 'dkpdf_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' )  ) {

			$removed_content = '';

		// if not returns the content inside the shortcode
		} else {

			$removed_content = $content;

		}

	} else {

		// if is pdf returns an empty string
		if( $pdf ) {

			$removed_content = '';

		// if not returns the content inside the shortcode
		} else {

			$removed_content = $content;

		}

	}

	return $removed_content;
*/
}
add_shortcode( 'dkpdf-remove', 'dkpdf_remove_shortcode' );

/**
* [dkpdf-pagebreak]
* Allows adding page breaks for sending content after this shortcode to the next page.
* Uses <pagebreak /> http://mpdf1.com/manual/index.php?tid=108
* @return string
*/
function dkpdf_pagebreak_shortcode( $atts, $content = null ) {

	$pdf = get_query_var( 'pdf' );

  	if( apply_filters( 'dkpdf_hide_button_isset', isset( $_POST['dkpdfg_action_create'] ) ) ) {
    	if ( $pdf || apply_filters( 'dkpdf_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create' )  ) {

			$output = '<pagebreak />';

		} else {

			$output = '';

		}

	} else {

		if( $pdf ) {

			$output = '<pagebreak />';

		} else {

			$output = '';

		}

	}

	return $output;

}

add_shortcode( 'dkpdf-pagebreak', 'dkpdf_pagebreak_shortcode' );
