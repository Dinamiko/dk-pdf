<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF;

use LogicException;
use Dinamiko\DKPDF\Vendor\Psr\Container\ContainerInterface;

class Container {
	private static $container = null;

	public static function container(): ContainerInterface {
		if ( ! self::$container ) {
			throw new LogicException( 'No container, probably called too early when the plugin is not initialized yet.' );
		}

		return self::$container;
	}

	public static function get_container(): ContainerInterface {
		return self::container();
	}

	/**
	 * Init the container.
	 *
	 * @param ContainerInterface $container The app container.
	 */
	public static function init( ContainerInterface $container ): void {
		self::$container = $container;
	}
}
