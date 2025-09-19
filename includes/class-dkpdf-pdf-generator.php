<?php

use Dinamiko\DKPDF\Vendor\Mpdf\Config\ConfigVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Config\FontVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Mpdf;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DKPDF_PDF_Generator {

	public function __construct() {
		add_action( 'wp', array( $this, 'handle_pdf_request' ) );
	}

	/**
	 * Handle PDF generation requests
	 */
	public function handle_pdf_request( $query ) {
		$pdf = get_query_var( 'pdf' );
		if ( ! $pdf || ! is_numeric( $pdf ) ) {
			return;
		}

		// Set up the post context for PDF generation
		$this->setup_post_context( $pdf );

		// For debugging - output HTML only if admin and output=html param is provided
		$output = isset( $_GET['output'] ) ? sanitize_text_field( $_GET['output'] ) : '';
		if ( $output === 'html' && current_user_can( 'manage_options' ) ) {
			$this->output_html_debug();

			return;
		}

		$this->generate_pdf();
	}

	/**
	 * Set up post context for PDF generation
	 *
	 * @param int $post_id Post ID to set up context for
	 */
	private function setup_post_context( $post_id ) {
		global $post, $wp_query;

		// Get the post object
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Set up global post data
		setup_postdata( $post );

		// Ensure wp_query is properly set up
		$wp_query->is_single = true;
		$wp_query->is_singular = true;
		$wp_query->post = $post;
		$wp_query->posts = array( $post );
		$wp_query->post_count = 1;
		$wp_query->found_posts = 1;
	}

	/**
	 * Output HTML for debugging purposes
	 */
	private function output_html_debug() {
		$template_content = dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) );
		echo preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $template_content );
		exit;
	}

	/**
	 * Generate and output PDF
	 */
	public function generate_pdf() {
		require_once realpath( __DIR__ . '/..' ) . '/vendor/autoload.php';

		$mpdf = $this->create_mpdf_instance();
		$this->configure_mpdf_settings( $mpdf );
		$this->add_content_to_mpdf( $mpdf );
		$this->set_document_properties( $mpdf );
		$this->output_pdf( $mpdf );
	}

	/**
	 * Create and configure mPDF instance
	 *
	 * @return Mpdf
	 */
	private function create_mpdf_instance() {
		$config = $this->get_mpdf_config();
		return new Mpdf( $config );
	}

	/**
	 * Get mPDF configuration array
	 *
	 * @return array
	 */
	private function get_mpdf_config() {
		// Configure PDF options from settings
		$config = array(
			'tempDir'           => apply_filters( 'dkpdf_mpdf_temp_dir', realpath( __DIR__ . '/..' ) . '/tmp' ),
			'default_font_size' => get_option( 'dkpdf_font_size', '12' ),
			'format'            => get_option( 'dkpdf_page_orientation' ) == 'horizontal' ?
				apply_filters( 'dkpdf_pdf_format', 'A4' ) . '-L' :
				apply_filters( 'dkpdf_pdf_format', 'A4' ),
			'margin_left'       => get_option( 'dkpdf_margin_left', '15' ),
			'margin_right'      => get_option( 'dkpdf_margin_right', '15' ),
			'margin_top'        => get_option( 'dkpdf_margin_top', '50' ),
			'margin_bottom'     => get_option( 'dkpdf_margin_bottom', '30' ),
			'margin_header'     => get_option( 'dkpdf_margin_header', '15' ),
		);

		// Add font configuration
		$default_config      = ( new ConfigVariables() )->getDefaults();
		$default_font_config = ( new FontVariables() )->getDefaults();

		$config['fontDir']  = apply_filters( 'dkpdf_mpdf_font_dir', $default_config['fontDir'] );
		$config['fontdata'] = apply_filters( 'dkpdf_mpdf_font_data', $default_font_config['fontdata'] );

		// Apply final config filter
		return apply_filters( 'dkpdf_mpdf_config', $config );
	}

	/**
	 * Configure mPDF settings like protection and columns
	 *
	 * @param Mpdf $mpdf
	 */
	private function configure_mpdf_settings( $mpdf ) {
		// Set protection if enabled
		if ( get_option( 'dkpdf_enable_protection' ) == 'on' ) {
			$mpdf->SetProtection( get_option( 'dkpdf_grant_permissions', array() ) );
		}

		// Enable column mode if configured
		if ( get_option( 'dkpdf_keep_columns' ) == 'on' ) {
			$mpdf->keepColumns = true;
		}
	}

	/**
	 * Add content to mPDF instance
	 *
	 * @param Mpdf $mpdf
	 */
	private function add_content_to_mpdf( $mpdf ) {
		// Set header and footer
		$mpdf->SetHTMLHeader( dkpdf_get_template( 'dkpdf-header' ) );
		$mpdf->SetHTMLFooter( dkpdf_get_template( 'dkpdf-footer' ) );

		// Write content
		$mpdf->WriteHTML( apply_filters( 'dkpdf_before_content', '' ) );
		$mpdf->WriteHTML( dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) ) );
		$mpdf->WriteHTML( apply_filters( 'dkpdf_after_content', '' ) );
	}

	/**
	 * Set PDF document properties
	 *
	 * @param Mpdf $mpdf
	 */
	private function set_document_properties( $mpdf ) {
		global $post;
		$title = '';

		if ( $post && isset( $post->ID ) ) {
			$title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );
		}

		// Fallback if no title available
		if ( empty( $title ) ) {
			$title = 'PDF Document';
		}

		$mpdf->SetTitle( $title );
		$mpdf->SetAuthor( apply_filters( 'dkpdf_pdf_author', get_bloginfo( 'name' ) ) );
	}

	/**
	 * Output the generated PDF
	 *
	 * @param Mpdf $mpdf
	 */
	private function output_pdf( $mpdf ) {
		global $post;
		$title = '';

		if ( $post && isset( $post->ID ) ) {
			$title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );
		}

		// Fallback if no title available
		if ( empty( $title ) ) {
			$title = 'PDF Document';
		}

		$action = get_option( 'dkpdf_pdfbutton_action', 'open' ) == 'open' ? 'I' : 'D';
		$mpdf->Output( $title . '.pdf', $action );
		exit;
	}
}