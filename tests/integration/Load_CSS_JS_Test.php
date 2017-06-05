<?php
class Load_CSS_JS_Test extends \Codeception\TestCase\WPTestCase {

  function test_includes() {
    $this->assertFileExists( DKPDF_PLUGIN_DIR . 'includes/dkpdf-load-js-css.php' );
  }

  function test_file_hooks() {
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', 'dkpdf_enqueue_styles' ) );
    $this->assertNotFalse( has_action( 'wp_enqueue_scripts', 'dkpdf_enqueue_scripts' ) );
    $this->assertNotFalse( has_action( 'admin_enqueue_scripts', 'dkpdf_admin_enqueue_scripts' ) );
    $this->assertNotFalse( has_action( 'admin_enqueue_scripts', 'dkpdf_admin_enqueue_styles' ) );
	}

  function test_enqueue_frontend_styles() {
    dkpdf_enqueue_styles();
    $this->assertTrue( wp_style_is( 'font-awesome', 'enqueued' ) );
    $this->assertTrue( wp_style_is( 'dkpdf-frontend', 'enqueued' ) );
  }

  function test_enqueue_frontend_scripts() {
    dkpdf_enqueue_scripts();
    $this->assertTrue( wp_script_is( 'dkpdf-frontend', 'enqueued' ) );
  }

  /*
  Fails because admin scripts are enqueued only if we're in dkpdf admin page
  TODO try to fix, understand why is failing.
  function test_enqueue_admin_styles( $hook = '' ) {
    dkpdf_admin_enqueue_styles();
    $this->assertTrue( wp_style_is( 'dkpdf-admin', 'enqueued' ) );
  }

  function test_enqueue_admin_scripts( $hook = '' ) {
    dkpdf_admin_enqueue_scripts();
    $this->assertTrue( wp_script_is( 'dkpdf-settings-admin', 'enqueued' ) );
    $this->assertTrue( wp_script_is( 'dkpdf-ace', 'enqueued' ) );
    $this->assertTrue( wp_script_is( 'dkpdf-admin', 'enqueued' ) );
  }
  */

}
