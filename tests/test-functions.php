<?php

class FunctionsTest extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

	}

	public function tearDown() {
		parent::tearDown();

	}

	// test dkpdf_get_post_types()
	function test_dkpdf_get_post_types() {

		$this->assertInternalType( 'array', dkpdf_get_post_types() );	

	}

}

