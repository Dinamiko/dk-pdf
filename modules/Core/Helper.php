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

	/**
	 * Get formatted custom fields display for a post
	 *
	 * @param int $post_id The post ID to get custom fields for
	 * @return string Formatted HTML for custom fields display
	 */
	public function get_custom_fields_display( int $post_id ): string {
		// Check if we should display custom fields (not using legacy template)
		$selected_template = get_option( 'dkpdf_selected_template', '' );
		if ( empty( $selected_template ) ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		// Get selected custom fields for this post type
		$selected_fields = get_option( 'dkpdf_custom_fields_' . $post->post_type, array() );
		if ( ! is_array( $selected_fields ) || empty( $selected_fields ) ) {
			return '';
		}

		$output = '';
		$field_count = 0;

		foreach ( $selected_fields as $field_key ) {
			// Skip empty field keys
			if ( empty( $field_key ) ) {
				continue;
			}

			$field_value = get_post_meta( $post_id, $field_key, true );

			// Skip empty values
			if ( empty( $field_value ) ) {
				continue;
			}

			// Convert field key to readable format (snake_case to Title Case)
			$field_label = $this->format_field_label( $field_key );

			$output .= '<div class="custom-field-item">';
			$output .= '<strong>' . esc_html( $field_label ) . ':</strong> ';
			$output .= esc_html( $field_value );
			$output .= '</div>' . "\n";

			$field_count++;
		}

		// Only return content if we have fields to display
		if ( $field_count > 0 ) {
			return '<div class="custom-fields-section">' . "\n" .
			       $output .
			       '</div>' . "\n";
		}

		return '';
	}

	/**
	 * Convert field key to readable label format
	 *
	 * @param string $field_key The field key to format
	 * @return string Formatted field label
	 */
	private function format_field_label( string $field_key ): string {
		// Replace underscores with spaces
		$label = str_replace( '_', ' ', $field_key );

		// Convert to title case
		$label = ucwords( $label );

		return $label;
	}
}