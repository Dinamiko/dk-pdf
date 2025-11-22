<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\WooCommerce;

class Integration {

	/**
	 * Determine WooCommerce-specific templates
	 *
	 * @param string $template Current template
	 * @return string Modified template name
	 */
	public function determine_woocommerce_template( string $template ): string {
		// If no template set is selected, return current template
		if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
			return $template;
		}

		// Check for single product page
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'dkpdf-single-product';
		}

		// Check for shop page or product archive
		if ( ( function_exists( 'is_shop' ) && is_shop() ) ||
		     ( function_exists( 'is_product_category' ) && is_product_category() ) ||
		     ( function_exists( 'is_product_tag' ) && is_product_tag() ) ) {
			return 'dkpdf-archive-product';
		}

		return $template;
	}

	/**
	 * Adds a PDF button to the shop category page before the products list
	 */
	public function add_shop_button(): void {
		// Only show button if template is selected
		if ( ! get_option( 'dkpdf_selected_template', '' ) ) {
			return;
		}

		// Don't show button if position is set to shortcode only
		if ( get_option( 'dkpdf_pdfbutton_position' ) === 'shortcode' ) {
			return;
		}

		$option_taxonomies = get_option( 'dkpdf_pdfbutton_taxonomies', array() );

		// Check if we're on a taxonomy page that should show the button
		if ( ( function_exists( 'is_product_category' ) && is_product_category() ) ||
		     ( function_exists( 'is_product_tag' ) && is_product_tag() ) ) {
			$queried_object = get_queried_object();
			if ( $queried_object instanceof \WP_Term && ! empty( $option_taxonomies ) ) {
				if ( ! in_array( $queried_object->taxonomy, $option_taxonomies ) ) {
					return;
				}
			} else {
				return;
			}
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			// For shop page, only show if product_cat is enabled (as it's the main product archive)
			if ( empty( $option_taxonomies ) || ! in_array( 'product_cat', $option_taxonomies ) ) {
				return;
			}
		} else {
			return;
		}

		echo $this->get_button_html();
	}

	/**
	 * Adds a PDF button to the single product page
	 */
	public function add_product_button(): void {
		// Don't show button if position is set to shortcode only
		if ( get_option( 'dkpdf_pdfbutton_position' ) === 'shortcode' ) {
			return;
		}

		$option_post_types = get_option( 'dkpdf_pdfbutton_post_types', array() );
		global $post;

		if ( ! in_array( get_post_type( $post ), $option_post_types ) ) {
			return;
		}

		echo $this->get_button_html();
	}

	/**
	 * Get the HTML for the PDF button
	 *
	 * @return string Button HTML
	 */
	private function get_button_html(): string {
		$container = \Dinamiko\DKPDF\Container::get_container();
		$template_renderer = $container->get( 'template.renderer' );

		// Use different templates for single product vs archive pages
		if ( function_exists( 'is_product' ) && is_product() ) {
			// Single product page - use regular button template
			return $template_renderer->get_template( 'dkpdf-button' );
		} else {
			// Archive/shop pages - use archive button template
			return $template_renderer->get_template( 'dkpdf-button-archive' );
		}
	}
}