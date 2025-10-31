<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\PDF;

use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Dinamiko\DKPDF\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class PDFModule implements ServiceModule, ExecutableModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'pdf.context_manager'  => static fn( $container ) => new ContextManager(),
			'pdf.title_resolver'   => static fn( $container ) => new TitleResolver(),
			'pdf.document_builder' => static fn( $container ) => new DocumentBuilder(
				$container->get( 'template.renderer' )
			),
			'pdf.generator'        => static fn( $container ) => new Generator(
				$container->get( 'template.renderer' ),
				$container->get( 'pdf.document_builder' ),
				$container->get( 'pdf.context_manager' ),
				$container->get( 'pdf.title_resolver' )
			),
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
