<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

class ProgrammaticGenerator {

	private DocumentBuilder $documentBuilder;
	private ContextManager $contextManager;
	private TitleResolver $titleResolver;

	public function __construct(
		DocumentBuilder $documentBuilder,
		ContextManager $contextManager,
		TitleResolver $titleResolver
	) {
		$this->documentBuilder = $documentBuilder;
		$this->contextManager  = $contextManager;
		$this->titleResolver   = $titleResolver;
	}

	/**
	 * Generate a PDF for a post and save it to a file
	 *
	 * @param int   $post_id The post ID to generate PDF for.
	 * @param array $args    Optional arguments:
	 *                       - 'output_path' (string) Full file path for the PDF.
	 *                       - 'title' (string) Override PDF document title.
	 * @return string|\WP_Error File path on success, WP_Error on failure.
	 */
	public function generate( int $post_id, array $args = [] ) {
		$backup = $this->backupGlobals();

		try {
			// Set the pdf query var so legacy templates work
			set_query_var( 'pdf', (string) $post_id );

			$result = $this->contextManager->setupContext( $post_id );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$title = $args['title'] ?? $this->titleResolver->resolveTitle();

			$output_path = $this->resolveOutputPath( $post_id, $title, $args );
			if ( is_wp_error( $output_path ) ) {
				return $output_path;
			}

			$dir = dirname( $output_path );
			if ( ! wp_mkdir_p( $dir ) ) {
				return new \WP_Error(
					'directory_creation_failed',
					sprintf(
						/* translators: %s: directory path */
						__( 'Could not create directory: %s', 'dkpdf' ),
						$dir
					)
				);
			}

			return $this->documentBuilder->generateToFile( $title, $output_path );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'pdf_generation_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'PDF generation failed: %s', 'dkpdf' ),
					$e->getMessage()
				)
			);
		} finally {
			$this->restoreGlobals( $backup );
		}
	}

	/**
	 * @return string|\WP_Error
	 */
	private function resolveOutputPath( int $post_id, string $title, array $args ) {
		if ( ! empty( $args['output_path'] ) ) {
			return apply_filters( 'dkpdf_programmatic_output_path', $args['output_path'], $post_id, $args );
		}

		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new \WP_Error(
				'upload_dir_error',
				sprintf(
					/* translators: %s: error message */
					__( 'Upload directory error: %s', 'dkpdf' ),
					$upload_dir['error']
				)
			);
		}

		$dir             = trailingslashit( $upload_dir['basedir'] ) . 'dkpdf';
		$sanitized_title = sanitize_file_name( $title );
		$filename        = sprintf( '%d-%s-%d.pdf', $post_id, $sanitized_title, time() );
		$file_path       = trailingslashit( $dir ) . $filename;

		return apply_filters( 'dkpdf_programmatic_output_path', $file_path, $post_id, $args );
	}

	private function backupGlobals(): array {
		global $post, $wp_query;

		return [
			'post'          => $post,
			'wp_query'      => clone $wp_query,
			'pdf_query_var' => get_query_var( 'pdf', '' ),
		];
	}

	private function restoreGlobals( array $backup ): void {
		global $post, $wp_query;

		$post     = $backup['post'];
		$wp_query = $backup['wp_query'];

		set_query_var( 'pdf', $backup['pdf_query_var'] );

		if ( $post ) {
			setup_postdata( $post );
		}
	}
}
