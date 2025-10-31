<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Frontend;

use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class FrontendModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'frontend.button_manager'        => static fn( $container ) => new ButtonManager( $container->get( 'template.loader' ) ),
			'frontend.wordpress_integration' => static fn( $container ) => new WordPressIntegration( $container->get( 'template.loader' ) ),
			'frontend.asset_loader'          => static fn() => new AssetLoader(),
		];
	}

	public function run( ContainerInterface $container ): bool {
		$button_manager = $container->get( 'frontend.button_manager' );
		assert( $button_manager instanceof ButtonManager );

		add_filter( 'the_content', function ( string $content ) use ( $button_manager ) {
			return $button_manager->display_pdf_button( $content );
		} );

		$asset_loader = $container->get( 'frontend.asset_loader' );
		assert( $asset_loader instanceof AssetLoader );

		add_action( 'wp_enqueue_scripts', function () use ( $asset_loader ) {
			$asset_loader->enqueue_styles();
		}, 15 );

		add_action( 'wp_enqueue_scripts', function () use ( $asset_loader ) {
			$asset_loader->enqueue_scripts();
		}, 10 );

		add_action( 'admin_enqueue_scripts', function () use ( $asset_loader ) {
			$asset_loader->admin_enqueue_scripts();
		}, 10, 1 );

		add_action( 'admin_enqueue_scripts', function () use ( $asset_loader ) {
			$asset_loader->admin_enqueue_styles();
		}, 10, 1 );

		$wordpress_integration = $container->get( 'frontend.wordpress_integration' );
		assert( $wordpress_integration instanceof WordPressIntegration );

		add_filter( 'query_vars', function ( array $vars ) use ( $wordpress_integration ) {
			return $wordpress_integration->add_query_vars( $vars );
		} );

		add_filter( 'dkpdf_content_template', function ( string $template ) use ( $wordpress_integration ) {
			return $wordpress_integration->determine_template( $template );
		} );

		add_filter( 'get_the_archive_description', function ( string $description ) use ( $wordpress_integration ) {
			return $wordpress_integration->add_archive_button( $description );
		} );

		return true;
	}
}
