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
		$selected_font = get_option( 'dkpdf_default_font', 'DejaVuSans' );
		$custom_fonts = get_option( 'dkpdf_custom_fonts', array() );

		// Validate selected font exists
		$font_exists = false;

		// Check if it's a custom font
		if ( isset( $custom_fonts[ strtolower( $selected_font ) ] ) ) {
			$font_exists = true;
		}

		// Check if it's a core font (in uploads directory)
		$upload_dir = wp_upload_dir();
		$fonts_dir = $upload_dir['basedir'] . '/dkpdf-fonts';
		$core_font_path = $fonts_dir . '/' . $selected_font;
		if ( ! str_ends_with( $core_font_path, '.ttf' ) ) {
			$core_font_path .= '.ttf';
		}
		if ( file_exists( $core_font_path ) ) {
			$font_exists = true;
		}

		// Fallback logic
		if ( ! $font_exists ) {
			// Get first available font
			if ( ! empty( $custom_fonts ) ) {
				$selected_font = key( $custom_fonts );
			} else {
				// Default to DejaVuSans (mPDF default)
				$selected_font = 'DejaVuSans';
			}
		}

		return strtolower( $selected_font );
	}

	private function getCustomFontData(): array {
		$upload_dir = wp_upload_dir();
		$fonts_dir  = $upload_dir['basedir'] . '/dkpdf-fonts';
		$fontdata   = array();

		if ( ! is_dir( $fonts_dir ) ) {
			return $fontdata;
		}

		// Get font families from WordPress options
		$custom_fonts = get_option( 'dkpdf_custom_fonts', array() );

		// Check if using new font family structure
		if ( ! empty( $custom_fonts ) && ! isset( $custom_fonts[0] ) ) {
			$first_family = reset( $custom_fonts );

			// New format with font families
			if ( isset( $first_family['family_name'] ) && isset( $first_family['variants'] ) ) {
				foreach ( $custom_fonts as $font_key => $family ) {
					// Only register families with Regular variant (R is mandatory for mPDF)
					if ( ! isset( $family['variants']['R'] ) ) {
						continue;
					}

					// Build font data with all available variants
					$font_config = array();

					foreach ( array( 'R', 'B', 'I', 'BI' ) as $variant ) {
						if ( isset( $family['variants'][ $variant ] ) ) {
							$font_config[ $variant ] = $family['variants'][ $variant ];
						}
					}

					// Register font family with mPDF
					$fontdata[ $font_key ] = $font_config;
				}

				return $fontdata;
			}
		}

		// Fallback: Old format or core fonts - scan filesystem
		$font_files = array_merge(
			glob( $fonts_dir . '/*.ttf' ) ?: array(),
			glob( $fonts_dir . '/*.TTF' ) ?: array()
		);

		if ( empty( $font_files ) ) {
			return $fontdata;
		}

		foreach ( $font_files as $font_file ) {
			$basename  = basename( $font_file );
			$font_name = preg_replace( '/\.ttf$/i', '', $basename );
			$font_key  = strtolower( $font_name );

			// Register font with basic configuration (regular weight only)
			$fontdata[ $font_key ] = array(
				'R' => $basename,
			);
		}

		return $fontdata;
	}

	private function getMpdfConfig(): array {
		// Configure PDF options from settings
		$config = array(
			'tempDir'                 => apply_filters( 'dkpdf_mpdf_temp_dir', realpath( __DIR__ . '/../..' ) . '/tmp' ),
			'default_font_size'       => get_option( 'dkpdf_font_size', '12' ),
			'default_font'            => $this->getSelectedFont(),
			'format'                  => get_option( 'dkpdf_page_orientation' ) == 'horizontal' ?
				apply_filters( 'dkpdf_pdf_format', 'A4' ) . '-L' :
				apply_filters( 'dkpdf_pdf_format', 'A4' ),
			'margin_left'             => get_option( 'dkpdf_margin_left', '15' ),
			'margin_right'            => get_option( 'dkpdf_margin_right', '15' ),
			'margin_top'              => get_option( 'dkpdf_margin_top', '50' ),
			'margin_bottom'           => get_option( 'dkpdf_margin_bottom', '30' ),
			'margin_header'           => get_option( 'dkpdf_margin_header', '15' ),
			'whitelistStreamWrappers' => array( 'https' ),
		);

		// Auto language detection for non-Latin scripts (Arabic, Hebrew, CJK, Thai, etc.)
		if ( get_option( 'dkpdf_auto_language_detection' ) === 'on' ) {
			$config['autoScriptToLang'] = true;
			$config['autoLangToFont']   = true;
		}

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
		// Set RTL direction if enabled
		if ( get_option( 'dkpdf_enable_rtl' ) === 'on' ) {
			$mpdf->SetDirectionality( 'rtl' );
		}

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
		$mpdf->SetHTMLHeader( $this->sanitizeContent( $this->renderer->get_template( 'dkpdf-header' ) ) );
		$mpdf->SetHTMLFooter( $this->sanitizeContent( $this->renderer->get_template( 'dkpdf-footer' ) ) );

		$mpdf->WriteHTML( $this->sanitizeContent( apply_filters( 'dkpdf_before_content', '' ) ) );
		$mpdf->WriteHTML( $this->sanitizeContent(
			$this->renderer->get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) )
		) );
		$mpdf->WriteHTML( $this->sanitizeContent( apply_filters( 'dkpdf_after_content', '' ) ) );
	}

	private function sanitizeContent( string $html ): string {
		$html = preg_replace_callback(
			'/<img([^>]*)\ssrc=["\']([^"\']+)["\']([^>]*)>/i',
			function ( $matches ) {
				$url = $matches[2];
				if ( $this->isUrlAllowed( $url ) ) {
					return $matches[0];
				}
				return '';
			},
			$html
		);

		$html = preg_replace_callback(
			'/url\s*\(\s*["\']?([^"\')\s]+)["\']?\s*\)/i',
			function ( $matches ) {
				$url = $matches[1];
				if ( $this->isUrlAllowed( $url ) ) {
					return $matches[0];
				}
				return 'url()';
			},
			$html
		);

		return $html;
	}

	private function isUrlAllowed( string $url ): bool {
		$url = trim( $url );

		if ( empty( $url ) ) {
			return true;
		}

		if ( str_starts_with( $url, 'data:' ) ) {
			return true;
		}

		if ( ! preg_match( '~^[a-zA-Z][a-zA-Z0-9+.-]*://~', $url ) ) {
			return true;
		}

		$site_url = get_site_url();
		if ( str_starts_with( $url, $site_url ) ) {
			return true;
		}

		return wp_http_validate_url( $url ) !== false;
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
