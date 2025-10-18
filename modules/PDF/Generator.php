<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

use Dinamiko\DKPDF\Template\TemplateRenderer;

class Generator {

	private const DEBUG_OUTPUT_HTML = 'html';

	private TemplateRenderer $renderer;
	private DocumentBuilder $documentBuilder;
	private ContextManager $contextManager;

	public function __construct( TemplateRenderer $renderer, DocumentBuilder $documentBuilder, ContextManager $contextManager ) {
		$this->renderer        = $renderer;
		$this->documentBuilder = $documentBuilder;
		$this->contextManager  = $contextManager;
	}

	public function handle_pdf_request( $wp = null ): void {
		$pdf = get_query_var( 'pdf' );
		if ( ! $pdf ) {
			return;
		}

		// Start output buffering early to prevent WordPress warnings
		if ( ! ob_get_level() ) {
			ob_start();
		}

		// Set up the appropriate context for PDF generation
		$this->contextManager->setupContext( $pdf );

		// For debugging - output HTML only if admin and output=html param is provided
		$output = isset( $_GET['output'] ) ? sanitize_text_field( $_GET['output'] ) : '';
		if ( $output === self::DEBUG_OUTPUT_HTML && current_user_can( 'manage_options' ) ) {
			$this->output_html_debug();
			return;
		}

		$this->generate_pdf();
	}

	private function generate_pdf(): void {
		try {
			$title = $this->get_pdf_title();
			$this->documentBuilder->generate( $title );
		} catch ( \Exception $e ) {
			// Clean any output buffers before showing error
			while ( ob_get_level() ) {
				ob_end_clean();
			}

			if ( current_user_can( 'manage_options' ) ) {
				wp_die( 'PDF Generation Error: ' . esc_html( $e->getMessage() ) );
			} else {
				wp_die( 'Unable to generate PDF. Please try again later.' );
			}
		}
	}

	private function output_html_debug(): void {
		$template_content = $this->renderer->get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) );
		echo preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $template_content );
		exit;
	}

	/**
	 * Get the PDF title based on current context
	 *
	 * @return string The PDF title
	 */
	private function get_pdf_title(): string {
		global $post, $wp_query;
		$title = '';

		// Determine title based on context
		if ( $post && isset( $post->ID ) ) {
			// Single post/page context
			$title = get_the_title( $post->ID );
		} elseif ( isset( $wp_query->queried_object ) ) {
			// Archive context
			if ( $wp_query->queried_object instanceof \WP_Term ) {
				$title = $wp_query->queried_object->name;
			} elseif ( $wp_query->queried_object instanceof \WP_Post ) {
				$title = $wp_query->queried_object->post_title;
			}
		}

		// Fallback if no title available
		if ( empty( $title ) ) {
			if ( function_exists( 'is_shop' ) && is_shop() ) {
				$title = __( 'Shop', 'dkpdf' );
			} else {
				$title = 'PDF Document';
			}
		}

		return apply_filters( 'dkpdf_pdf_filename', $title );
	}
}
