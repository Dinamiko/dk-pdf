<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Frontend;

use Dinamiko\DKPDF\Template\TemplateLoader;

class ButtonManager {

	private TemplateLoader $template_loader;

	public function __construct( TemplateLoader $template_loader ) {
		$this->template_loader = $template_loader;
	}

	/**
	 * Displays PDF button based on settings and context
	 *
	 * @param string $content Post content
	 * @return string Modified content with PDF button
	 */
	public function display_pdf_button( string $content ): string {
		$pdf = get_query_var( 'pdf' );

		// Don't display button in PDF view or during form submission
		if ( $this->should_hide_button( $pdf ) ) {
			remove_shortcode( 'dkpdf-button' );
			return str_replace( '[dkpdf-button]', '', $content );
		}

		// Check if button should be shown based on current context
		if ( ! $this->should_show_button() ) {
			return $content;
		}

		// Get button position setting
		$pdfbutton_position = get_option( 'dkpdf_pdfbutton_position', 'before' );

		// Return content if using shortcode
		if ( $pdfbutton_position == 'shortcode' ) {
			return $content;
		}

		// Add button before or after content
		$button = $this->get_button_html();

		if ( $pdfbutton_position == 'before' ) {
			return $button . $content;
		} elseif ( $pdfbutton_position == 'after' ) {
			return $content . $button;
		}

		return $content;
	}

	/**
	 * Check if button should be hidden
	 *
	 * @param mixed $pdf PDF query var value
	 * @return bool
	 */
	private function should_hide_button( $pdf ): bool {
		return ( isset( $_POST['dkpdfg_action_create'] ) &&
		        ( $_POST['dkpdfg_action_create'] === 'dkpdfg_action_create' || $pdf ) ) || $pdf;
	}

	/**
	 * Check if button should be shown based on current context
	 *
	 * @return bool
	 */
	private function should_show_button(): bool {
		// Get settings
		$option_post_types = (array) get_option( 'dkpdf_pdfbutton_post_types', array() );

		// Check if button should be shown based on current context
		if ( is_singular() && ! empty( $option_post_types ) ) {
			global $post;
			if ( ! in_array( get_post_type( $post ), $option_post_types ) || get_post_type( $post ) === 'product' ) {
				return false;
			}
			return true;
		}

		// Not a singular post type
		return false;
	}

	/**
	 * Get the HTML for the PDF button
	 *
	 * @return string Button HTML
	 */
	private function get_button_html(): string {
		ob_start();
		$this->template_loader->get_template_part( 'dkpdf-button' );
		return ob_get_clean();
	}
}
