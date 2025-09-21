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
			'shortcode.manager' => static fn( $container ) => new ShortcodeManager( $container->get( 'template.loader' ) ),
		];
	}

	public function run( ContainerInterface $container ): bool {
		$shortcode_manager = $container->get( 'shortcode.manager' );
		assert( $shortcode_manager instanceof ShortcodeManager );

		add_shortcode( 'dkpdf-button', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			$shortcode_manager->button_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-remove', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			$shortcode_manager->remove_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-pagebreak', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			$shortcode_manager->pagebreak_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-columns', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			$shortcode_manager->columns_shortcode($atts, $content);
		} );

		add_shortcode( 'dkpdf-columnbreak', function (array $atts, ?string $content = null) use ( $shortcode_manager ) {
			$shortcode_manager->columnbreak_shortcode($atts, $content);
		} );

		return true;
	}
}
