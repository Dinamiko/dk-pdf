<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

use Dinamiko\DKPDF\Vendor\Mpdf\Config\ConfigVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Config\FontVariables;
use Dinamiko\DKPDF\Vendor\Mpdf\Mpdf;

class Generator {

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
		$this->setup_context( $pdf );

		// For debugging - output HTML only if admin and output=html param is provided
		$output = isset( $_GET['output'] ) ? sanitize_text_field( $_GET['output'] ) : '';
		if ( $output === 'html' && current_user_can( 'manage_options' ) ) {
			$this->output_html_debug();
			return;
		}

		$this->generate_pdf();
	}

	private function generate_pdf(): void {
		try {
			require_once realpath( __DIR__ . '/../..' ) . '/vendor/autoload.php';

			$mpdf = $this->create_mpdf_instance();
			$this->configure_mpdf_settings( $mpdf );
			$this->add_content_to_mpdf( $mpdf );
			$this->set_document_properties( $mpdf );
			$this->output_pdf( $mpdf );
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

	private function setup_context( $pdf_param ): void {
		if ( is_numeric( $pdf_param ) ) {
			// Single post/page context
			$this->setup_post_context( (int) $pdf_param );
		} else {
			// Archive context (shop, category, tag, etc.)
			$this->setup_archive_context( $pdf_param );
		}
	}

	private function setup_post_context( int $post_id ): void {
		global $post, $wp_query;

		// Get the post object
		$post = get_post( $post_id );
		if ( ! $post || $post->post_status !== 'publish' ) {
			throw new \Exception( 'Post not found or not published: ' . $post_id );
		}

		// Set up global post data
		setup_postdata( $post );

		// Ensure wp_query is properly set up for single posts
		$wp_query->is_single = true;
		$wp_query->is_singular = true;
		$wp_query->is_archive = false;
		$wp_query->is_shop = false;
		$wp_query->is_tax = false;
		$wp_query->post = $post;
		$wp_query->posts = array( $post );
		$wp_query->post_count = 1;
		$wp_query->found_posts = 1;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = $post_id;
	}

	private function setup_archive_context( string $archive_type ): void {
		global $wp_query;

		// Reset query flags for archive context
		$wp_query->is_single = false;
		$wp_query->is_singular = false;
		$wp_query->is_archive = true;
		$wp_query->is_shop = false;
		$wp_query->is_tax = false;
		$wp_query->is_category = false;
		$wp_query->is_tag = false;
		$wp_query->is_post_type_archive = false;
		$wp_query->post = null;
		$wp_query->posts = array();
		$wp_query->post_count = 0;

		// Handle different archive types
		switch ( $archive_type ) {
			case 'shop':
				if ( ! function_exists( 'wc_get_page_id' ) ) {
					throw new \Exception( 'WooCommerce not active, cannot generate shop PDF' );
				}

				$shop_page_id = wc_get_page_id( 'shop' );
				if ( $shop_page_id <= 0 ) {
					throw new \Exception( 'Shop page not configured in WooCommerce' );
				}

				$shop_page = get_post( $shop_page_id );
				if ( ! $shop_page || $shop_page->post_status !== 'publish' ) {
					throw new \Exception( 'Shop page not found or not published' );
				}

				$wp_query->queried_object_id = $shop_page_id;
				$wp_query->queried_object = $shop_page;
				$wp_query->is_shop = true;
				$wp_query->is_post_type_archive = true;
				break;

			default:
				// Handle taxonomy archives (product categories, tags, regular categories/tags, etc.)
				if ( strpos( $archive_type, 'product_cat_' ) === 0 ) {
					$term_id = (int) str_replace( 'product_cat_', '', $archive_type );
					$term = get_term( $term_id, 'product_cat' );
					if ( ! $term || is_wp_error( $term ) ) {
						throw new \Exception( 'Product category not found: ' . $term_id );
					}
					$wp_query->queried_object = $term;
					$wp_query->queried_object_id = $term_id;
					$wp_query->is_category = true;
					$wp_query->is_tax = true;
				} elseif ( strpos( $archive_type, 'product_tag_' ) === 0 ) {
					$term_id = (int) str_replace( 'product_tag_', '', $archive_type );
					$term = get_term( $term_id, 'product_tag' );
					if ( ! $term || is_wp_error( $term ) ) {
						throw new \Exception( 'Product tag not found: ' . $term_id );
					}
					$wp_query->queried_object = $term;
					$wp_query->queried_object_id = $term_id;
					$wp_query->is_tag = true;
					$wp_query->is_tax = true;
				} elseif ( strpos( $archive_type, 'category_' ) === 0 ) {
					$term_id = (int) str_replace( 'category_', '', $archive_type );
					$term = get_term( $term_id, 'category' );
					if ( ! $term || is_wp_error( $term ) ) {
						throw new \Exception( 'Category not found: ' . $term_id );
					}
					$wp_query->queried_object = $term;
					$wp_query->queried_object_id = $term_id;
					$wp_query->is_category = true;
					$wp_query->is_tax = true;
				} elseif ( strpos( $archive_type, 'tag_' ) === 0 ) {
					$term_id = (int) str_replace( 'tag_', '', $archive_type );
					$term = get_term( $term_id, 'post_tag' );
					if ( ! $term || is_wp_error( $term ) ) {
						throw new \Exception( 'Tag not found: ' . $term_id );
					}
					$wp_query->queried_object = $term;
					$wp_query->queried_object_id = $term_id;
					$wp_query->is_tag = true;
					$wp_query->is_tax = true;
				} else {
					// Handle generic taxonomy_termid format
					$parts = explode( '_', $archive_type );
					if ( count( $parts ) >= 2 ) {
						$term_id = (int) array_pop( $parts );
						$taxonomy = implode( '_', $parts );
						$term = get_term( $term_id, $taxonomy );
						if ( ! $term || is_wp_error( $term ) ) {
							throw new \Exception( 'Term not found: ' . $taxonomy . ' - ' . $term_id );
						}
						$wp_query->queried_object = $term;
						$wp_query->queried_object_id = $term_id;
						$wp_query->is_tax = true;
						if ( $taxonomy === 'category' ) {
							$wp_query->is_category = true;
						} elseif ( $taxonomy === 'post_tag' ) {
							$wp_query->is_tag = true;
						}
					} else {
						throw new \Exception( 'Unknown archive type: ' . $archive_type );
					}
				}
				break;
		}
	}

	private function output_html_debug(): void {
		$template_content = \dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) );
		echo preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $template_content );
		exit;
	}

	private function create_mpdf_instance(): Mpdf {
		$config = $this->get_mpdf_config();
		return new Mpdf( $config );
	}

	private function get_mpdf_config(): array {
		// Configure PDF options from settings
		$config = array(
			'tempDir'           => apply_filters( 'dkpdf_mpdf_temp_dir', realpath( __DIR__ . '/../..' ) . '/tmp' ),
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

	private function configure_mpdf_settings( Mpdf $mpdf ): void {
		// Set protection if enabled
		if ( get_option( 'dkpdf_enable_protection' ) == 'on' ) {
			$mpdf->SetProtection( get_option( 'dkpdf_grant_permissions', array() ) );
		}

		// Enable column mode if configured
		if ( get_option( 'dkpdf_keep_columns' ) == 'on' ) {
			$mpdf->keepColumns = true;
		}
	}

	private function add_content_to_mpdf( Mpdf $mpdf ): void {
		// Set header and footer
		$mpdf->SetHTMLHeader( \dkpdf_get_template( 'dkpdf-header' ) );
		$mpdf->SetHTMLFooter( \dkpdf_get_template( 'dkpdf-footer' ) );

		// Write content
		$mpdf->WriteHTML( apply_filters( 'dkpdf_before_content', '' ) );
		$mpdf->WriteHTML( \dkpdf_get_template( apply_filters( 'dkpdf_content_template', 'dkpdf-index' ) ) );
		$mpdf->WriteHTML( apply_filters( 'dkpdf_after_content', '' ) );
	}

	private function set_document_properties( Mpdf $mpdf ): void {
		global $post, $wp_query;
		$title = '';

		// Determine title based on context
		if ( $post && isset( $post->ID ) ) {
			// Single post/page context
			$title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );
		} elseif ( isset( $wp_query->queried_object ) ) {
			// Archive context
			if ( $wp_query->queried_object instanceof \WP_Term ) {
				$title = apply_filters( 'dkpdf_pdf_filename', $wp_query->queried_object->name );
			} elseif ( $wp_query->queried_object instanceof \WP_Post ) {
				$title = apply_filters( 'dkpdf_pdf_filename', $wp_query->queried_object->post_title );
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

		$mpdf->SetTitle( $title );
		$mpdf->SetAuthor( apply_filters( 'dkpdf_pdf_author', get_bloginfo( 'name' ) ) );
	}

	private function output_pdf( Mpdf $mpdf ): void {
		global $post, $wp_query;
		$title = '';

		// Determine filename based on context
		if ( $post && isset( $post->ID ) ) {
			// Single post/page context
			$title = apply_filters( 'dkpdf_pdf_filename', get_the_title( $post->ID ) );
		} elseif ( isset( $wp_query->queried_object ) ) {
			// Archive context
			if ( $wp_query->queried_object instanceof \WP_Term ) {
				$title = apply_filters( 'dkpdf_pdf_filename', $wp_query->queried_object->name );
			} elseif ( $wp_query->queried_object instanceof \WP_Post ) {
				$title = apply_filters( 'dkpdf_pdf_filename', $wp_query->queried_object->post_title );
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

		// Clean any previous output before sending PDF
		if ( ob_get_level() ) {
			ob_clean();
		}

		$action = get_option( 'dkpdf_pdfbutton_action', 'open' ) == 'open' ? 'I' : 'D';
		$mpdf->Output( $title . '.pdf', $action );
		exit;
	}
}
