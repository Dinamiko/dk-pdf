<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Frontend;

use Dinamiko\DKPDF\Template\TemplateLoader;

class WordPressIntegration {

	private TemplateLoader $template_loader;

	public function __construct( TemplateLoader $template_loader ) {
		$this->template_loader = $template_loader;
	}

	public function init(): void {
		$this->init_query_vars();
		$this->init_template_filters();
		$this->init_archive_integration();
	}

	/**
	 * Initialize query variable registration
	 */
	private function init_query_vars(): void {
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Initialize template selection filters
	 */
	private function init_template_filters(): void {
		add_filter( 'dkpdf_content_template', array( $this, 'determine_template' ) );
	}

	/**
	 * Initialize archive page integration
	 */
	private function init_archive_integration(): void {
		add_filter( 'get_the_archive_description', array( $this, 'add_archive_button' ) );
	}

	/**
	 * Adds 'pdf' query variable to WordPress
	 *
	 * @param array $vars Current query vars
	 * @return array Modified query vars
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'pdf';
		return $vars;
	}

	/**
	 * Determine which template to use based on page type
	 *
	 * @param string $template Current template
	 * @return string Modified template name
	 */
	public function determine_template( string $template ): string {
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
	public function add_archive_button( string $description ): string {
		if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
			return $description;
		}

		$option_taxonomies = get_option( 'dkpdf_pdfbutton_taxonomies', array() );
		$queried_object    = get_queried_object();

		if ( $queried_object instanceof \WP_Term && ! empty( $option_taxonomies ) ) {
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
	private function get_button_html(): string {
		ob_start();
		$this->template_loader->get_template_part( 'dkpdf-button-archive' );
		return ob_get_clean();
	}
}