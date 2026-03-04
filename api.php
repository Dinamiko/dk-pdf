<?php
/**
 * DK PDF Public API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate a PDF for a post and save it to a file.
 *
 * Must be called after plugins_loaded (priority 10).
 *
 * Usage:
 *   $path = dkpdf_generate( 42 );
 *   $path = dkpdf_generate( 42, [ 'output_path' => '/path/to/file.pdf' ] );
 *   $path = dkpdf_generate( 42, [ 'title' => 'Custom Title' ] );
 *
 * @param int   $post_id The post ID to generate PDF for.
 * @param array $args    Optional arguments:
 *                       - 'output_path' (string) Full file path for the PDF.
 *                       - 'title' (string) Override PDF document title/filename.
 *                       - 'format' (string) Page size, e.g. 'A4', 'Letter', 'A4-L'.
 * @return string|WP_Error File path on success, WP_Error on failure.
 */
function dkpdf_generate( int $post_id, array $args = [] ) {
	try {
		$container = \Dinamiko\DKPDF\Container::container();
	} catch ( \LogicException $e ) {
		return new WP_Error(
			'plugin_not_initialized',
			__( 'DK PDF plugin is not initialized yet. Make sure to call this function after plugins_loaded.', 'dkpdf' )
		);
	}

	$generator = $container->get( 'pdf.programmatic_generator' );

	return $generator->generate( $post_id, $args );
}
