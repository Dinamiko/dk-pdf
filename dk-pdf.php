<?php
/*
 * Plugin Name: DK PDF
 * Version: 2.0.3
 * Description: WordPress to PDF made easy.
 * Author: Emili Castells
 * Author URI: https://dinamiko.dev
 * Requires at least: 3.9
 * Requires PHP: 8.0
 * Tested up to: 6.8
 * License: MIT
 * Text Domain: dkpdf
 * Domain Path: /languages/
 */

declare( strict_types=1 );

namespace Dinamiko\DKPDF;

use Dinamiko\DKPDF\Admin\AdminModule;
use Dinamiko\DKPDF\Core\CoreModule;
use Dinamiko\DKPDF\Frontend\FrontendModule;
use Dinamiko\DKPDF\PDF\PDFModule;
use Dinamiko\DKPDF\Shortcode\ShortcodeModule;
use Dinamiko\DKPDF\Template\TemplateModule;
use Dinamiko\DKPDF\WooCommerce\WooCommerceModule;
use Inpsyde\Modularity\Package;
use Inpsyde\Modularity\Properties\PluginProperties;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DKPDF' ) ) {

	#[AllowDynamicProperties]
	final class DKPDF {

		private static $instance;
		public $admin;

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

			if ( ! defined( 'DKPDF_VERSION' ) ) {
				define( 'DKPDF_VERSION', '2.0.2' );
			}
			if ( ! defined( 'DKPDF_PLUGIN_DIR' ) ) {
				define( 'DKPDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}
			if ( ! defined( 'DKPDF_PLUGIN_URL' ) ) {
				define( 'DKPDFPLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}
			if ( ! defined( 'DKPDF_PLUGIN_FILE' ) ) {
				define( 'DKPDF_PLUGIN_FILE', __FILE__ );
			}

		}

		private function includes() {
			// All functionality is now handled by the modular system
			// Legacy includes have been migrated to modules

			// Global helper functions for backward compatibility
			if ( ! function_exists( 'dkpdf_get_template' ) ) {
				function dkpdf_get_template( $template_name ) {
					$container = \Dinamiko\DKPDF\Container::get_container();
					$template_renderer = $container->get( 'template.renderer' );
					return $template_renderer->get_template( $template_name );
				}
			}

			if ( ! function_exists( 'dkpdf_get_post_types' ) ) {
				function dkpdf_get_post_types() {
					$container = \Dinamiko\DKPDF\Container::get_container();
					$helper = $container->get( 'core.helper' );
					return $helper->get_post_types();
				}
			}

			if ( ! function_exists( 'dkpdf_get_taxonomies' ) ) {
				function dkpdf_get_taxonomies() {
					$container = \Dinamiko\DKPDF\Container::get_container();
					$helper = $container->get( 'core.helper' );
					return $helper->get_taxonomies();
				}
			}
		}

		public function __clone() {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', 'dkpdf' ), DKPDF_VERSION );
		}

		public function __wakeup() {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', 'dkpdf' ), DKPDF_VERSION );
		}

	}

}

DKPDF::instance();

function plugin(): Package {
	static $package;

	if ( ! $package ) {
		$properties = PluginProperties::new( __FILE__ );
		$package    = Package::new( $properties )
		                     ->addModule( new CoreModule() )
		                     ->addModule( new TemplateModule() )
		                     ->addModule( new AdminModule() )
		                     ->addModule( new FrontendModule() )
		                     ->addModule( new ShortcodeModule() )
		                     ->addModule( new PDFModule() )
		                     ->addModule( new WooCommerceModule() );
	}

	/** @var Package $package */
	return $package;
}

add_action(
	'plugins_loaded',
	static function (): void {
		if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
			include_once __DIR__ . '/vendor/autoload.php';
		}

		plugin()->boot();

		Container::init( plugin()->container() );
	}
);
