<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DKPDF_WordPress_Integration {

	public function __construct() {
		$this->init_query_vars();
		$this->init_template_filters();
		$this->init_archive_integration();
	}

	/**
	 * Initialize query variable registration
	 */
	private function init_query_vars() {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Initialize template selection filters
	 */
	private function init_template_filters() {
		add_filter( 'dkpdf_content_template', array( $this, 'determine_template' ) );
	}

	/**
	 * Initialize archive page integration
	 */
	private function init_archive_integration() {
		add_filter( 'get_the_archive_description', array( $this, 'add_archive_button' ) );
	}

	/**
	 * Adds 'pdf' query variable to WordPress
	 *
	 * @param array $vars Current query vars
	 * @return array Modified query vars
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'pdf';
		return $vars;
	}

	/**
	 * Determine which template to use based on page type
	 *
	 * @param string $template Current template
	 * @return string Modified template name
	 */
	public function determine_template( $template ) {
		// If no template set is selected, return legacy template
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
	}

	/**
	 * Add PDF button to archive descriptions if applicable
	 *
	 * @param string $description Archive description
	 * @return string Modified description with button
	 */
	public function add_archive_button( $description ) {
		if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
			return $description;
		}

		$option_taxonomies = get_option( 'dkpdf_pdfbutton_taxonomies', array() );
		$queried_object    = get_queried_object();

		if ( $queried_object instanceof WP_Term && ! empty( $option_taxonomies ) ) {
			if ( in_array( $queried_object->taxonomy, $option_taxonomies ) ) {
				$button = $this->get_button_html();
				return $description . $button;
			}
		}

		return $description;
	}

	/**
	 * Get the HTML for the PDF button
	 *
	 * @return string Button HTML
	 */
	private function get_button_html() {
		ob_start();
		( new DKPDF_Template_Loader() )->get_template_part( 'dkpdf-button' );
		return ob_get_clean();
	}
}