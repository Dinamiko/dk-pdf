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

		$this->manager->setupContext( $post_id );

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

	public function test_throws_exception_for_nonexistent_post(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Post not found' );

		$this->manager->setupContext( 999999 );
	}

	public function test_throws_exception_for_draft_post(): void {
		$post_id = wp_insert_post([
			'post_title' => 'Draft Post',
			'post_status' => 'draft',
		]);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'not published' );

		try {
			$this->manager->setupContext( $post_id );
		} finally {
			wp_delete_post( $post_id, true );
		}
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

		$this->manager->setupContext( "category_{$category_id}" );

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

	public function test_throws_exception_for_nonexistent_category(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'not found' );

		$this->manager->setupContext( 'category_999999' );
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

		$this->manager->setupContext( "tag_{$tag_id}" );

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

		$this->manager->setupContext( 'shop' );

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

	public function test_throws_exception_for_shop_without_woocommerce(): void {
		if ( function_exists( 'wc_get_page_id' ) ) {
			$this->markTestSkipped( 'WooCommerce is active, cannot test this scenario' );
		}

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'WooCommerce not active' );

		$this->manager->setupContext( 'shop' );
	}

	public function test_setup_product_category_archive(): void {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			$this->markTestSkipped( 'WooCommerce not active' );
		}

		// Create product category
		$term = wp_insert_term( 'Test Product Category', 'product_cat' );
		$term_id = $term['term_id'];

		$this->manager->setupContext( "product_cat_{$term_id}" );

		global $wp_query;

		// Verify query flags
		$this->assertTrue( $wp_query->is_archive );
		$this->assertTrue( $wp_query->is_tax );
		$this->assertEquals( $term_id, $wp_query->queried_object_id );

		// Clean up
		wp_delete_term( $term_id, 'product_cat' );
	}

	// ===== EDGE CASES =====

	public function test_invalid_archive_type(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Unknown archive type' );

		// Archive type with no underscore triggers "Unknown archive type"
		$this->manager->setupContext( 'invalidarchivetype' );
	}
}
