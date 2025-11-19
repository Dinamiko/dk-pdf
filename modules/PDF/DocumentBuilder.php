<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

use Dinamiko\DKPDF\Template\TemplateRenderer;
use Dinamiko\DKPDF\Vendor\Mpdf\Config\ConfigVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Config\FontVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Mpdf;

class DocumentBuilder {

	private TemplateRenderer $renderer;

	public function __construct( TemplateRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Generate and output a PDF document
	 *
	 * @param string $title The title/filename for the PDF
	 * @throws \Exception If PDF generation fails
	 */
	public function generate( string $title ): void {
		require_once realpath( __DIR__ . '/../..' ) . '/vendor/autoload.php';

		$mpdf = $this->createMpdfInstance();
		$this->configureMpdfSettings( $mpdf );
		$this->addContentToMpdf( $mpdf );
		$this->setDocumentProperties( $mpdf, $title );
		$this->outputPdf( $mpdf, $title );
	}

	private function createMpdfInstance(): Mpdf {
		$config = $this->getMpdfConfig();
		return new Mpdf( $config );
	}

	private function getSelectedFont(): string {
		$font = get_option( 'dkpdf_font_downloader', 'DejaVuSans' );
		return strtolower( $font );
	}

	private function getCustomFontData(): array {
		$upload_dir = wp_upload_dir();
		$fonts_dir  = $upload_dir['basedir'] . '/dkpdf-fonts';
		$fontdata   = array();

		if ( ! is_dir( $fonts_dir ) ) {
			return $fontdata;
		}

		$font_files = glob( $fonts_dir . '/*.ttf' );
		if ( ! $font_files ) {
			return $fontdata;
		}

		foreach ( $font_files as $font_file ) {
			$font_name = basename( $font_file, '.ttf' );
			$font_key  = strtolower( $font_name );

			// Register font with basic configuration (regular weight only)
			$fontdata[ $font_key ] = array(
				'R' => $font_name . '.ttf',
			);
		}

		return $fontdata;
	}

	private function getMpdfConfig(): array {
		// Configure PDF options from settings
		$config = array(
			'tempDir'           => apply_filters( 'dkpdf_mpdf_temp_dir', realpath( __DIR__ . '/../..' ) . '/tmp' ),
			'default_font_size' => get_option( 'dkpdf_font_size', '12' ),
			'default_font'      => $this->getSelectedFont(),
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

		// Include custom fonts directory
		$upload_dir        = wp_upload_dir();
		$custom_fonts_dir  = $upload_dir['basedir'] . '/dkpdf-fonts';
		$font_directories  = $default_config['fontDir'];

		if ( is_dir( $custom_fonts_dir ) ) {
			$font_directories[] = $custom_fonts_dir;
		}

		$config['fontDir'] = apply_filters( 'dkpdf_mpdf_font_dir', $font_directories );

		// Merge custom fonts with default fontdata
		$custom_fontdata = $this->getCustomFontData();
		$config['fontdata'] = apply_filters(
			'dkpdf_mpdf_font_data',
			array_merge( $default_font_config['fontdata'], $custom_fontdata )
		);

		// Apply final config filter
		return apply_filters( 'dkpdf_mpdf_config', $config );
	}

	private function configureMpdfSettings( Mpdf $mpdf ): void {
		// Set protection if enabled
		if ( get_option( 'dkpdf_enable_protection' ) == 'on' ) {
			$mpdf->SetProtection( get_option( 'dkpdf_grant_permissions', array() ) );
		}

		// Enable column mode if configured
		if ( get_option( 'dkpdf_keep_columns' ) == 'on' ) {
			$mpdf->keepColumns = true;
		}
	}

	private function addContentToMpdf( Mpdf $mpdf ): void {
		// Set header and footer
		$mpdf->SetHTMLHeader( $this->renderer->get_template( 'dkpdf-header' ) );
		$mpdf->SetHTMLFooter( $this->renderer->get_template( 'dkpdf-footer' ) );

		// Write content
		$mpdf->WriteHTML( apply_filters( 'dkpdf_before_content', '' ) );
		$mpdf->WriteHTML( $this->renderer->get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) ) );
		$mpdf->WriteHTML( apply_filters( 'dkpdf_after_content', '' ) );
	}

	private function setDocumentProperties( Mpdf $mpdf, string $title ): void {
		$mpdf->SetTitle( $title );
		$mpdf->SetAuthor( apply_filters( 'dkpdf_pdf_author', get_bloginfo( 'name' ) ) );
	}

	private function outputPdf( Mpdf $mpdf, string $title ): void {
		// Clean any previous output before sending PDF
		if ( ob_get_level() ) {
			ob_clean();
		}

		$action = get_option( 'dkpdf_pdfbutton_action', 'open' ) == 'open' ? 'I' : 'D';
		$mpdf->Output( $title . '.pdf', $action );
		exit;
	}
}
