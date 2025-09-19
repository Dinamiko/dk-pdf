<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Admin;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class AdminModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'admin.api' => static fn() => new Api(),
			'admin.settings' => static fn($container) => new Settings($container->get('admin.api')),
		];
	}

	public function run( ContainerInterface $container ): bool {
		add_action( 'save_post', function () use ( $container ) {
			$api = $container->get( 'admin.api' );
			assert($api instanceof Api);

			$api->save_meta_boxes();
		} );

		add_action( 'init', function() use ($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			$settings->init_settings();
		}, 20 );

		add_action( 'admin_init', function() use ($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			$settings->register_settings();
		} );

		add_action( 'admin_menu', function() use($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			$settings->add_menu_item();
		} );

		add_filter( 'plugin_action_links_' . plugin_basename( DKPDF_PLUGIN_FILE ), function($links) use($container) {
			$settings = $container->get( 'admin.settings' );
			assert($settings instanceof Settings);

			return $settings->add_settings_link($links);
		});

		return true;
	}
}
