<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Core;

use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class CoreModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'core.helper' => static fn() => new Helper(),
		];
	}

	public function run( ContainerInterface $container ): bool {
		$helper = $container->get( 'core.helper' );
		assert( $helper instanceof Helper );

		// Register filter to make custom fields display available to templates
		add_filter( 'dkpdf_get_custom_fields_display', function ( string $content, int $post_id ) use ( $helper ) {
			return $helper->get_custom_fields_display( $post_id );
		}, 10, 2 );

		return true;
	}
}