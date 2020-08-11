<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Di;

use Essence\Configurable;
use Closure;



/**
 *	A simple dependency injection container.
 *	Inspired by Pimple (https://github.com/fabpot/Pimple).
 *
 *	@package Essence.Di
 */

class Container {

	use Configurable;



	/**
	 *	Container properties.
	 *
	 *	@var array
	 */

	protected $_properties = [ ];



	/**
	 *	Returns the value of the given property.
	 *
	 *	@param string $property Property name.
	 *	@param mixed $default Default value to be returned in case the property
	 *		doesn't exists.
	 *	@return mixed The property value, or the result of the closure execution
	 *		if property is a closure, or $default.
	 */

	public function get( $property, $default = null ) {

		$value = $default;

		if ( $this->has( $property )) {
			$value = $this->_properties[ $property ];

			if ( $value instanceof Closure ) {
				$value = $value( $this );
			}
		}

		return $value;
	}



	/**
	 *	Returns a wrapper that memoizes the result of the given closure.
	 *
	 *	@param Closure $closure Closure to wrap.
	 *	@return Closure Wrapper.
	 */

	public static function unique( Closure $closure ) {

		return function( $Container ) use ( $closure ) {
			static $result = null;

			if ( $result === null ) {
				$result = $closure( $Container );
			}

			return $result;
		};
	}
}
