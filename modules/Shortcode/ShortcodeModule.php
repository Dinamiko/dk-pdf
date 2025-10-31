<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Shortcode;

use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class ShortcodeModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'shortcode.manager' => static fn( $container ) => new ShortcodeManager( $container->get( 'template.loader' ) ),
		];
	}

	public function run( ContainerInterface $container ): bool {
		$shortcode_manager = $container->get( 'shortcode.manager' );
		assert( $shortcode_manager instanceof ShortcodeManager );

		add_shortcode( 'dkpdf-button', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			return $shortcode_manager->button_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-remove', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			return $shortcode_manager->remove_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-pagebreak', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			return $shortcode_manager->pagebreak_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-columns', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			return $shortcode_manager->columns_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-columnbreak', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			return $shortcode_manager->columnbreak_shortcode($atts, $content);
		} );

		return true;
	}
}
