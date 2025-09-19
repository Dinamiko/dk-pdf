<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\WooCommerce;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class WooCommerceModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'woocommerce.integration' => static fn() => new Integration(),
		];
	}

	public function run( ContainerInterface $container ): bool {
		// Only initialize if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return true;
		}

		$integration = $container->get( 'woocommerce.integration' );
		assert($integration instanceof Integration);

		add_filter( 'dkpdf_content_template', function( $template ) use ( $integration ) {
			return $integration->determine_woocommerce_template( $template );
		}, 5 );

		add_action( 'woocommerce_before_shop_loop', function() use ( $integration ) {
			$integration->add_shop_button();
		} );

		add_action( 'woocommerce_product_meta_start', function() use ( $integration ) {
			$integration->add_product_button();
		} );

		return true;
	}
}