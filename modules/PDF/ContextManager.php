<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

/**
 * Manages WordPress query context setup for PDF generation
 *
 * This class handles all manipulation of global WordPress query objects
 * ($wp_query and $post) to set up the appropriate context for PDF generation.
 */
class ContextManager {

	// Archive type constants
	private const ARCHIVE_TYPE_SHOP = 'shop';
	private const PREFIX_PRODUCT_CAT = 'product_cat_';
	private const PREFIX_PRODUCT_TAG = 'product_tag_';
	private const PREFIX_CATEGORY = 'category_';
	private const PREFIX_TAG = 'tag_';

	// Taxonomy configuration map
	private const TAXONOMY_CONFIG = [
		self::PREFIX_PRODUCT_CAT => [
			'taxonomy'  => 'product_cat',
			'post_type' => 'product',
			'is_tag'    => false,
		],
		self::PREFIX_PRODUCT_TAG => [
			'taxonomy'  => 'product_tag',
			'post_type' => 'product',
			'is_tag'    => true,
		],
		self::PREFIX_CATEGORY => [
			'taxonomy'  => 'category',
			'post_type' => 'post',
			'is_tag'    => false,
		],
		self::PREFIX_TAG => [
			'taxonomy'  => 'post_tag',
			'post_type' => 'post',
			'is_tag'    => true,
		],
	];

	/**
	 * Set up WordPress context for PDF generation
	 *
	 * @param mixed $pdf_param The PDF parameter (post ID or archive type)
	 * @throws \Exception If context setup fails
	 */
	public function setupContext( $pdf_param ): void {
		if ( is_numeric( $pdf_param ) ) {
			// Single post/page context
			$this->setupPostContext( (int) $pdf_param );
		} else {
			// Archive context (shop, category, tag, etc.)
			$this->setupArchiveContext( $pdf_param );
		}
	}

	private function setupPostContext( int $post_id ): void {
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

	/**
	 * Set up shop archive context
	 */
	private function setupShopArchive(): void {
		global $wp_query;

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

		// Query shop products
		$this->queryArchivePosts( 'product', null, null );
	}

	/**
	 * Set up taxonomy archive context
	 *
	 * @param string $prefix The taxonomy prefix (e.g., 'product_cat_', 'category_')
	 * @param string $archive_type The full archive type string
	 */
	private function setupTaxonomyArchive( string $prefix, string $archive_type ): void {
		global $wp_query;

		$config = self::TAXONOMY_CONFIG[ $prefix ] ?? null;
		if ( ! $config ) {
			throw new \Exception( 'Unknown taxonomy prefix: ' . $prefix );
		}

		// Extract term ID from archive type
		$term_id = (int) str_replace( $prefix, '', $archive_type );
		$term = get_term( $term_id, $config['taxonomy'] );

		if ( ! $term || is_wp_error( $term ) ) {
			throw new \Exception( ucfirst( $config['taxonomy'] ) . ' not found: ' . $term_id );
		}

		// Set query properties
		$wp_query->queried_object = $term;
		$wp_query->queried_object_id = $term_id;
		$wp_query->is_tax = true;

		if ( $config['is_tag'] ) {
			$wp_query->is_tag = true;
		} else {
			$wp_query->is_category = true;
		}

		// Query posts for this taxonomy term
		$this->queryArchivePosts( $config['post_type'], $config['taxonomy'], $term_id );
	}

	private function setupArchiveContext( string $archive_type ): void {
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

		// Route to appropriate handler based on archive type
		if ( $archive_type === self::ARCHIVE_TYPE_SHOP ) {
			$this->setupShopArchive();
			return;
		}

		// Check for known taxonomy prefixes
		foreach ( array_keys( self::TAXONOMY_CONFIG ) as $prefix ) {
			if ( str_starts_with( $archive_type, $prefix ) ) {
				$this->setupTaxonomyArchive( $prefix, $archive_type );
				return;
			}
		}

		// Handle generic taxonomy_termid format for custom taxonomies
		$this->setupGenericTaxonomyArchive( $archive_type );
	}

	/**
	 * Set up generic taxonomy archive for custom taxonomies
	 *
	 * @param string $archive_type The archive type string
	 */
	private function setupGenericTaxonomyArchive( string $archive_type ): void {
		global $wp_query;

		$parts = explode( '_', $archive_type );
		if ( count( $parts ) < 2 ) {
			throw new \Exception( 'Unknown archive type: ' . $archive_type );
		}

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

		// Query posts for this taxonomy term
		$post_type = $this->getPostTypeForTaxonomy( $taxonomy );
		$this->queryArchivePosts( $post_type, $taxonomy, $term_id );
	}

	/**
	 * Query posts for archive pages
	 *
	 * @param string $post_type The post type to query
	 * @param string|null $taxonomy The taxonomy name, null for post type archives
	 * @param int|null $term_id The term ID for taxonomy archives
	 */
	private function queryArchivePosts( string $post_type, ?string $taxonomy = null, ?int $term_id = null ): void {
		global $wp_query;

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $this->getPostsPerPage( $post_type, $taxonomy ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Add taxonomy query if this is a taxonomy archive
		if ( $taxonomy && $term_id ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			);
		}

		// Apply filters to allow customization
		$args = apply_filters( 'dkpdf_archive_query_args', $args, $post_type, $taxonomy, $term_id );

		// Create a new WP_Query to get the posts
		$query = new \WP_Query( $args );

		// Update the global $wp_query with our results
		$wp_query->posts = $query->posts;
		$wp_query->post_count = $query->post_count;
		$wp_query->found_posts = $query->found_posts;
		$wp_query->max_num_pages = $query->max_num_pages;
		$wp_query->current_post = -1;

		// Reset the post data
		if ( ! empty( $wp_query->posts ) ) {
			$wp_query->post = $wp_query->posts[0];
		}
	}

	/**
	 * Get the appropriate post type for a given taxonomy
	 *
	 * @param string $taxonomy The taxonomy name
	 * @return string The post type
	 */
	private function getPostTypeForTaxonomy( string $taxonomy ): string {
		// Get taxonomy object to determine associated post types
		$tax_object = get_taxonomy( $taxonomy );
		if ( $tax_object && ! empty( $tax_object->object_type ) ) {
			// Return the first associated post type
			return $tax_object->object_type[0];
		}

		// Fallback mappings for common taxonomies
		$taxonomy_post_type_map = array(
			'category'    => 'post',
			'post_tag'    => 'post',
			'product_cat' => 'product',
			'product_tag' => 'product',
		);

		return $taxonomy_post_type_map[ $taxonomy ] ?? 'post';
	}

	/**
	 * Determine the appropriate posts_per_page value based on archive type
	 *
	 * @param string $post_type The post type being queried
	 * @param string|null $taxonomy The taxonomy name, null for post type archives
	 * @return int Number of posts per page
	 */
	private function getPostsPerPage( string $post_type, ?string $taxonomy = null ): int {
		$default_posts_per_page = 100;

		if ( $post_type === 'product' ) {
			$posts_per_page = (int) get_option( 'dkpdf_wc_archive_posts_per_page', $default_posts_per_page );
		} else {
			$posts_per_page = (int) get_option( 'dkpdf_taxonomy_posts_per_page', $default_posts_per_page );
		}

		if ( $posts_per_page < 1 ) {
			$posts_per_page = $default_posts_per_page;
		}

		return (int) apply_filters( 'dkpdf_posts_per_page', $posts_per_page, $post_type, $taxonomy );
	}
}
