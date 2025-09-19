<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Core;

class Helper {

	/**
	 * Returns array of post types
	 *
	 * @return array Array of available post types
	 */
	public function get_post_types(): array {
		$custom_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
		$post_arr     = array( 'post' => 'post', 'page' => 'page', 'attachment' => 'attachment' );

		foreach ( $custom_types as $post_type ) {
			$post_arr[ $post_type ] = $post_type;
		}

		return apply_filters( 'dkpdf_posts_arr', $post_arr );
	}

	/**
	 * Returns array of taxonomies
	 *
	 * @return array Array of available taxonomies
	 */
	public function get_taxonomies(): array {
		$custom_taxonomies  = get_taxonomies( array( 'public' => true, '_builtin' => false ) );
		$builtin_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => true ) );
		$all_taxonomies     = array_merge( $custom_taxonomies, $builtin_taxonomies );

		$tax_arr = array();
		foreach ( $all_taxonomies as $taxonomy ) {
			$tax_arr[ $taxonomy ] = $taxonomy;
		}

		// Remove unwanted taxonomies
		unset( $tax_arr['post_format'] );
		unset( $tax_arr['product_shipping_class'] );
		unset( $tax_arr['product_brand'] );

		return apply_filters( 'dkpdf_taxonomies_arr', $tax_arr );
	}
}