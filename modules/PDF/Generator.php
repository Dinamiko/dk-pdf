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
}
