<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;
use Psr\Container\ContainerInterface;

class PDFModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'pdf.generator' => static fn() => new Generator(),
		];
	}

	public function run( ContainerInterface $container ): bool {
		add_action( 'wp', function ( $wp ) use ( $container ) {
			$generator = $container->get( 'pdf.generator' );
			assert($generator instanceof Generator);

			$generator->handle_pdf_request( $wp );
		} );

		return true;
	}
}