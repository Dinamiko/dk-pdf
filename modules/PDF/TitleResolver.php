<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

/**
 * Resolves PDF title/filename from WordPress context
 *
 * This class reads from the WordPress query context to determine
 * an appropriate title for the generated PDF document.
 */
class TitleResolver {

	/**
	 * Resolve the PDF title based on current WordPress context
	 *
	 * @return string The resolved PDF title
	 */
	public function resolveTitle(): string {
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
