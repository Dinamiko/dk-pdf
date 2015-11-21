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
* [dkpdf-remove]content to remove[/dkpdf-remove]
* This shortcode is used remove pieces of content in the generated PDF
* @return string 
*/
function dkpdf_remove_shortcode( $atts, $content = null ) {

	$pdf = get_query_var( 'pdf' );

	// if is pdf returns an empty string
	if( $pdf ) {

		$removed_content = '';

	// if not returns the content inside the shortcode	
	} else {

		$removed_content = $content;

	}

	return $removed_content;

}

add_shortcode( 'dkpdf-remove', 'dkpdf_remove_shortcode' );
