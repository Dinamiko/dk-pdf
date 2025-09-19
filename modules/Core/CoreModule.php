<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Core;

use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;

class CoreModule implements ServiceModule {
	use ModuleClassNameIdTrait;

	public function services(): array {
		return [
			'core.helper' => static fn() => new Helper(),
		];
	}
}