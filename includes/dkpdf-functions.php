<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns rendered template content
 */
function dkpdf_get_template( $template_name ) {
	$selected_template = get_option( 'dkpdf_selected_template', '' );
	$full_template_name = $selected_template . $template_name;

	$template = new DKPDF_Template_Loader;
	ob_start();
	$template->get_template_part( $full_template_name );
	$content = ob_get_clean();

	return $content;
}

/**
 * Returns array of post types
 */
function dkpdf_get_post_types() {
	return DKPDF_Helper::get_post_types();
}

/**
 * Returns array of taxonomies
 */
function dkpdf_get_taxonomies() {
	return DKPDF_Helper::get_taxonomies();
}