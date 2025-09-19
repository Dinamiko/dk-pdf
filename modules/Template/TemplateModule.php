<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Template;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;

class TemplateModule implements ServiceModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'template.loader' => static fn() => new TemplateLoader(),
			'template.renderer' => static fn($container) => new TemplateRenderer($container->get('template.loader')),
		];
	}
}