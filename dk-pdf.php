<?php
/*
 * Plugin Name: DK PDF
 * Version: 1.9.3
 * Plugin URI: http://wp.dinamiko.com/demos/dkpdf
 * Description: Create PDF documents from your WordPress pages
 * Author: Emili Castells
 * Author URI: http://www.dinamiko.com
 * Requires at least: 3.9
 * Tested up to: 4.8
 *
 * Text Domain: dkpdf
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'DKPDF' ) ) {

	final class DKPDF {

		private static $instance;

		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DKPDF ) ) {

				self::$instance = new DKPDF;

				self::$instance->setup_constants();

				add_action( 'plugins_loaded', array( self::$instance, 'dkpdf_load_textdomain' ) );

				self::$instance->includes();

			}

			return self::$instance;

		}

		public function dkpdf_load_textdomain() {

			load_plugin_textdomain( 'dkpdf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		}

		private function setup_constants() {

			if ( ! defined( 'DKPDF_VERSION' ) ) { define( 'DKPDF_VERSION', '1.9.3' ); }
			if ( ! defined( 'DKPDF_PLUGIN_DIR' ) ) { define( 'DKPDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); }
			if ( ! defined( 'DKPDF_PLUGIN_URL' ) ) { define( 'DKPDFPLUGIN_URL', plugin_dir_url( __FILE__ ) ); }
			if ( ! defined( 'DKPDF_PLUGIN_FILE' ) ) { define( 'DKPDF_PLUGIN_FILE', __FILE__ ); }

		}

		private function includes() {

			// settings / metaboxes
			if ( is_admin() ) {

				require_once DKPDF_PLUGIN_DIR . 'includes/class-dkpdf-settings.php';
				$settings = new DKPDF_Settings( $this );

				require_once DKPDF_PLUGIN_DIR . 'includes/class-dkpdf-admin-api.php';
				$this->admin = new DKPDF_Admin_API();

				require_once DKPDF_PLUGIN_DIR . 'includes/dkpdf-metaboxes.php';

			}

			// load css / js
			require_once DKPDF_PLUGIN_DIR . 'includes/dkpdf-load-js-css.php';

			// functions
			require_once DKPDF_PLUGIN_DIR . 'includes/dkpdf-functions.php';

			// shortcodes
			require_once DKPDF_PLUGIN_DIR . 'includes/class-dkpdf-template-loader.php';
			require_once DKPDF_PLUGIN_DIR . 'includes/dkpdf-shortcodes.php';

		}

		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dkpdf' ), DKPDF_VERSION );
		}

		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'dkpdf' ), DKPDF_VERSION );
		}

	}

}

function DKPDF() {

	return DKPDF::instance();

}

DKPDF();
