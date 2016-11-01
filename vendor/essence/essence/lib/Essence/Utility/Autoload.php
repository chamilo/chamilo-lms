<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Utility;



/**
 *	A simple PSR-0 compliant class loader.
 *
 *	@package Essence.Utility
 */

class Autoload {

	/**
	 *	Sets autoload up on the given path.
	 *
	 *	@param string $basePath Base include path for all class files.
	 */

	public static function setup( $basePath ) {

		$basePath = rtrim( $basePath, DIRECTORY_SEPARATOR );

		spl_autoload_register( function( $className ) use ( $basePath ) {
			if (strpos($className, 'Essence\\') === false) {
				return;
			}
			$path = $basePath
				. DIRECTORY_SEPARATOR
				. str_replace( '\\', DIRECTORY_SEPARATOR, $className )
				. '.php';

			if ( file_exists( $path )) {
				require_once $path;
			}
		});
	}
}
