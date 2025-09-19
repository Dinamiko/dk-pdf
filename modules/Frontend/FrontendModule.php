<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Frontend;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class FrontendModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'frontend.button_manager' => static fn($container) => new ButtonManager($container->get('template.loader')),
			'frontend.wordpress_integration' => static fn($container) => new WordPressIntegration($container->get('template.loader')),
			'frontend.asset_loader' => static fn() => new AssetLoader(),
		];
	}

	public function run( ContainerInterface $container ): bool {
		// Initialize button manager
		$button_manager = $container->get( 'frontend.button_manager' );
		assert($button_manager instanceof ButtonManager);
		$button_manager->init();

		// Initialize WordPress integration
		$wordpress_integration = $container->get( 'frontend.wordpress_integration' );
		assert($wordpress_integration instanceof WordPressIntegration);
		$wordpress_integration->init();

		// Initialize asset loader
		$asset_loader = $container->get( 'frontend.asset_loader' );
		assert($asset_loader instanceof AssetLoader);
		$asset_loader->init();

		return true;
	}
}