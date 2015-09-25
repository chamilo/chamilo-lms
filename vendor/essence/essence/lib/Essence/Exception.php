<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use Exception as NativeException;



/**
 *	The base exception class of the Essence API.
 *
 *	@package Essence
 */

class Exception extends NativeException {

	/**
	 *	Wraps a native PHP exception.
	 *
	 *	@param NativeException Native exception.
	 *	@return Exception Essence exception.
	 */

	public static function wrap( NativeException $Exception ) {

		return new Exception(
			$Exception->getMessage( ),
			$Exception->getCode( ),
			$Exception
		);
	}



	/**
	 *	An alias to fit the Essence coding style.
	 *	I'm probably mad.
	 *
	 *	@return string The exception message.
	 */

	public function message( ) {

		return $this->getMessage( );
	}
}
