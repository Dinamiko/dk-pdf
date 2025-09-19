<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Shortcode;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class ShortcodeModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'shortcode.manager' => static fn($container) => new ShortcodeManager($container->get('template.loader')),
		];
	}

	public function run( ContainerInterface $container ): bool {
		$shortcode_manager = $container->get( 'shortcode.manager' );
		assert($shortcode_manager instanceof ShortcodeManager);

		$shortcode_manager->init();

		return true;
	}
}