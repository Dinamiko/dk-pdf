<?php

class HooksTest extends WP_UnitTestCase {

	public function setUp() {

		parent::setUp();
		global $wp_filter;

		// filters
		$this->assertarrayHasKey( 'dkpdf_display_pdf_button', $wp_filter['the_content'][10] );
		$this->assertarrayHasKey( 'dkpdf_set_query_vars', $wp_filter['query_vars'][10] );

		// actions
		$this->assertarrayHasKey( 'dkpdf_output_pdf', $wp_filter['wp'][10] );

	}

	public function tearDown() {
		parent::tearDown();

	}

	function test_dummy() {}


}

