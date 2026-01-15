<?php
/*
 * Plugin Name: DK PDF
 * Version: 2.3.1
 * Description: WordPress to PDF made easy.
 * Author: Emili Castells
 * Author URI: https://dinamiko.dev
 * Requires at least: 3.9
 * Requires PHP: 8.0
 * Tested up to: 6.9
 * License: MIT
 * Text Domain: dkpdf
 * Domain Path: /languages/
 */

declare( strict_types=1 );

namespace Dinamiko\DKPDF;

use Dinamiko\DKPDF\Admin\AdminModule;
use Dinamiko\DKPDF\ButtonVisibility\ButtonVisibilityModule;
use Dinamiko\DKPDF\Core\CoreModule;
use Dinamiko\DKPDF\Frontend\FrontendModule;
use Dinamiko\DKPDF\PDF\PDFModule;
use Dinamiko\DKPDF\Shortcode\ShortcodeModule;
use Dinamiko\DKPDF\Template\TemplateModule;
use Dinamiko\DKPDF\WooCommerce\WooCommerceModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Package;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Properties\PluginProperties;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

! defined( 'DKPDF_VERSION' ) && define( 'DKPDF_VERSION', '2.3.1' );
! defined( 'DKPDF_PLUGIN_DIR' ) && define( 'DKPDF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'DKPDF_PLUGIN_URL' ) && define( 'DKPDF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
! defined( 'DKPDF_PLUGIN_FILE' ) && define( 'DKPDF_PLUGIN_FILE', __FILE__ );

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
		                     ->addModule( new WooCommerceModule() )
		                     ->addModule( new ButtonVisibilityModule() );

		$modules = apply_filters( 'dkpdf_modules', [] );
		foreach ( $modules as $module ) {
			$package->addModule( $module );
		}
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
