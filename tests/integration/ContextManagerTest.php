<?php
declare(strict_types=1);

namespace Dinamiko\DKPDF\Tests\Integration;

use Dinamiko\DKPDF\PDF\ContextManager;
use PHPUnit\Framework\TestCase;

class ContextManagerTest extends TestCase {

	private ContextManager $manager;

	public function setUp(): void {
		parent::setUp();
		$this->manager = new ContextManager();
	}

	public function tearDown(): void {
		// Clean up global state after each test
		wp_reset_postdata();
		parent::tearDown();
	}

	// ===== SINGLE POST TESTS =====

	public function test_setup_published_post_context(): void {
		$post_id = wp_insert_post([
			'post_title' => 'Test Post',
			'post_content' => 'Test content',
			'post_status' => 'publish',
		]);

		$result = $this->manager->setupContext( $post_id );

		// Should return true on success
		$this->assertTrue( $result );

		global $wp_query, $post;

		// Verify query flags
		$this->assertTrue( $wp_query->is_single );
		$this->assertTrue( $wp_query->is_singular );
		$this->assertFalse( $wp_query->is_archive );
		$this->assertFalse( $wp_query->is_shop );
		$this->assertFalse( $wp_query->is_tax );

		// Verify queried object
		$this->assertEquals( $post_id, $wp_query->queried_object_id );
		$this->assertInstanceOf( \WP_Post::class, $wp_query->queried_object );
		$this->assertEquals( 'Test Post', $wp_query->queried_object->post_title );

		// Verify global post
		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertEquals( $post_id, $post->ID );
		$this->assertEquals( 'Test Post', $post->post_title );

		// Verify posts array
		$this->assertCount( 1, $wp_query->posts );
		$this->assertEquals( $post_id, $wp_query->posts[0]->ID );

		wp_delete_post( $post_id, true );
	}

	public function test_returns_error_for_nonexistent_post(): void {
		$result = $this->manager->setupContext( 999999 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'post_not_found', $result->get_error_code() );
		$this->assertStringContainsString( 'not found', $result->get_error_message() );
	}

	public function test_returns_error_for_draft_post_without_permission(): void {
		$post_id = wp_insert_post([
			'post_title' => 'Draft Post',
			'post_status' => 'draft',
		]);

		// Simulate user without permission (not logged in)
		wp_set_current_user( 0 );

		$result = $this->manager->setupContext( $post_id );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'no_permission', $result->get_error_code() );
		$this->assertStringContainsString( 'permission', $result->get_error_message() );

		wp_delete_post( $post_id, true );
	}

	public function test_setup_private_post_context_with_permission(): void {
		// Create a user with edit_posts capability
		$user_id = wp_insert_user([
			'user_login' => 'testuser',
			'user_pass' => 'password',
			'role' => 'editor',
		]);
		wp_set_current_user( $user_id );

		$post_id = wp_insert_post([
			'post_title' => 'Private Post',
			'post_content' => 'Private content',
			'post_status' => 'private',
			'post_author' => $user_id,
		]);

		$result = $this->manager->setupContext( $post_id );

		// Should succeed because user has permission
		$this->assertTrue( $result );

		global $wp_query, $post;
		$this->assertEquals( $post_id, $post->ID );
		$this->assertEquals( 'Private Post', $post->post_title );

		// Clean up
		wp_delete_post( $post_id, true );
		// Load user functions if not loaded
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		wp_delete_user( $user_id );
		wp_set_current_user( 0 );
	}

	// ===== CATEGORY ARCHIVE TESTS =====

	public function test_setup_category_archive_context(): void {
		// Create category and posts
		$category = wp_insert_term( 'Test Category', 'category' );
		$category_id = $category['term_id'];
		$post_id_1 = wp_insert_post([
			'post_title' => 'Post 1',
			'post_status' => 'publish',
			'post_category' => [ $category_id ],
		]);
		$post_id_2 = wp_insert_post([
			'post_title' => 'Post 2',
			'post_status' => 'publish',
			'post_category' => [ $category_id ],
		]);

		$result = $this->manager->setupContext( "category_{$category_id}" );

		// Should return true on success
		$this->assertTrue( $result );

		global $wp_query;

		// Verify query flags
		$this->assertFalse( $wp_query->is_single );
		$this->assertFalse( $wp_query->is_singular );
		$this->assertTrue( $wp_query->is_archive );
		$this->assertTrue( $wp_query->is_tax );
		$this->assertTrue( $wp_query->is_category );
		$this->assertFalse( $wp_query->is_tag );

		// Verify queried object
		$this->assertEquals( $category_id, $wp_query->queried_object_id );
		$this->assertInstanceOf( \WP_Term::class, $wp_query->queried_object );
		$this->assertEquals( 'Test Category', $wp_query->queried_object->name );

		// Verify posts are queried
		$this->assertGreaterThanOrEqual( 2, $wp_query->post_count );

		// Clean up
		wp_delete_post( $post_id_1, true );
		wp_delete_post( $post_id_2, true );
		wp_delete_term( $category_id, 'category' );
	}

	public function test_returns_error_for_nonexistent_category(): void {
		$result = $this->manager->setupContext( 'category_999999' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'term_not_found', $result->get_error_code() );
		$this->assertStringContainsString( 'not found', $result->get_error_message() );
	}

	// ===== TAG ARCHIVE TESTS =====

	public function test_setup_tag_archive_context(): void {
		// Create tag and post
		$tag = wp_insert_term( 'test-tag', 'post_tag' );
		$tag_id = $tag['term_id'];

		$post_id = wp_insert_post([
			'post_title' => 'Tagged Post',
			'post_status' => 'publish',
			'tags_input' => [ 'test-tag' ],
		]);

		$result = $this->manager->setupContext( "tag_{$tag_id}" );

		// Should return true on success
		$this->assertTrue( $result );

		global $wp_query;

		// Verify query flags
		$this->assertTrue( $wp_query->is_archive );
		$this->assertTrue( $wp_query->is_tax );
		$this->assertTrue( $wp_query->is_tag );
		$this->assertFalse( $wp_query->is_category );

		// Verify queried object
		$this->assertEquals( $tag_id, $wp_query->queried_object_id );
		$this->assertEquals( 'test-tag', $wp_query->queried_object->slug );

		// Clean up
		wp_delete_post( $post_id, true );
		wp_delete_term( $tag_id, 'post_tag' );
	}

	// ===== WOOCOMMERCE TESTS =====

	public function test_setup_shop_archive_context(): void {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			$this->markTestSkipped( 'WooCommerce not active' );
		}

		$result = $this->manager->setupContext( 'shop' );

		// Should return true on success
		$this->assertTrue( $result );

		global $wp_query;

		// Verify query flags
		$this->assertFalse( $wp_query->is_single );
		$this->assertTrue( $wp_query->is_archive );
		$this->assertTrue( $wp_query->is_shop );
		$this->assertTrue( $wp_query->is_post_type_archive );

		// Verify shop page is queried object
		$shop_page_id = wc_get_page_id( 'shop' );
		$this->assertEquals( $shop_page_id, $wp_query->queried_object_id );
	}

	public function test_returns_error_for_shop_without_woocommerce(): void {
		if ( function_exists( 'wc_get_page_id' ) ) {
			$this->markTestSkipped( 'WooCommerce is active, cannot test this scenario' );
		}

		$result = $this->manager->setupContext( 'shop' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'woocommerce_not_active', $result->get_error_code() );
		$this->assertStringContainsString( 'WooCommerce', $result->get_error_message() );
	}

	public function test_setup_product_category_archive(): void {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			$this->markTestSkipped( 'WooCommerce not active' );
		}

		// Create product category
		$term = wp_insert_term( 'Test Product Category', 'product_cat' );
		$term_id = $term['term_id'];

		$result = $this->manager->setupContext( "product_cat_{$term_id}" );

		// Should return true on success
		$this->assertTrue( $result );

		global $wp_query;

		// Verify query flags
		$this->assertTrue( $wp_query->is_archive );
		$this->assertTrue( $wp_query->is_tax );
		$this->assertEquals( $term_id, $wp_query->queried_object_id );

		// Clean up
		wp_delete_term( $term_id, 'product_cat' );
	}

	// ===== EDGE CASES =====

	public function test_returns_error_for_invalid_archive_type(): void {
		// Archive type with no underscore triggers "invalid_archive_type" error
		$result = $this->manager->setupContext( 'invalidarchivetype' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_archive_type', $result->get_error_code() );
		$this->assertStringContainsString( 'Invalid', $result->get_error_message() );
	}
}
