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

	/**
	 * Returns array of custom fields for a specific post type
	 *
	 * @param string $post_type The post type to get custom fields for
	 * @return array Array of custom fields formatted for select options
	 */
	public function get_custom_fields_for_post_type( string $post_type ): array {
		global $wpdb;

		// Get all distinct meta keys for the specified post type
		// Exclude WordPress internal keys (starting with _) and plugin keys (starting with dkpdf_)
		$meta_keys = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT pm.meta_key
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = %s
			AND pm.meta_key NOT LIKE '\_%'
			AND pm.meta_key NOT LIKE 'dkpdf\_%'
			ORDER BY pm.meta_key",
			$post_type
		) );

		$custom_fields = array();

		// Format meta keys for select options
		foreach ( $meta_keys as $meta_key ) {
			$custom_fields[ $meta_key ] = $meta_key;
		}

		return apply_filters( 'dkpdf_custom_fields_for_post_type', $custom_fields, $post_type );
	}
}