<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

use Dinamiko\DKPDF\Template\TemplateRenderer;

class Generator {

	private const DEBUG_OUTPUT_HTML = 'html';

	private TemplateRenderer $renderer;
	private DocumentBuilder $documentBuilder;
	private ContextManager $contextManager;
	private TitleResolver $titleResolver;

	public function __construct( TemplateRenderer $renderer, DocumentBuilder $documentBuilder, ContextManager $contextManager, TitleResolver $titleResolver ) {
		$this->renderer        = $renderer;
		$this->documentBuilder = $documentBuilder;
		$this->contextManager  = $contextManager;
		$this->titleResolver   = $titleResolver;
	}

	public function handle_pdf_request( $wp = null ): void {
		$pdf = get_query_var( 'pdf' );
		if ( ! $pdf ) {
			return;
		}

		// Sanitize: only allow alphanumeric, underscores, and hyphens
		$pdf = preg_replace( '/[^a-zA-Z0-9_-]/', '', $pdf );
		if ( empty( $pdf ) ) {
			return;
		}

		// Start output buffering early to prevent WordPress warnings
		if ( ! ob_get_level() ) {
			ob_start();
		}

		// Set up the appropriate context for PDF generation
		$result = $this->contextManager->setupContext( $pdf );
		if ( is_wp_error( $result ) ) {
			$this->handle_error( $result );
			return;
		}

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
			$title = $this->titleResolver->resolveTitle();
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
	 * Handle errors gracefully with appropriate user messages
	 *
	 * @param \WP_Error $error The error object
	 */
	private function handle_error( \WP_Error $error ): void {
		// Clean any output buffers
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		if ( current_user_can( 'manage_options' ) ) {
			wp_die(
				sprintf(
					'<strong>%s</strong><br><br>%s',
					esc_html__( 'PDF Generation Error', 'dk-pdf' ),
					esc_html( $error->get_error_message() )
				),
				esc_html__( 'PDF Generation Error', 'dk-pdf' ),
				array( 'response' => 404 )
			);
		} else {
			wp_die(
				esc_html( $error->get_error_message() ),
				esc_html__( 'PDF Not Available', 'dk-pdf' ),
				array( 'response' => 404 )
			);
		}
	}
}
