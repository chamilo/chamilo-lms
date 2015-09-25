<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Cache\Engine;

use Essence\Cache\Engine;



/**
 *	Handles caching for a single session.
 *
 *	@package Essence.Cache.Engine
 */

class Volatile implements Engine {

	/**
	 *	Data.
	 *
	 *	@var array
	 */

	protected $_data = [ ];



	/**
	 *	{@inheritDoc}
	 */

	public function has( $key ) {

		return array_key_exists( $key, $this->_data );
	}



	/**
	 *	{@inheritDoc}
	 */

	public function get( $key, $default = null ) {

		return $this->has( $key )
			? $this->_data[ $key ]
			: $default;
	}



	/**
	 *	{@inheritDoc}
	 */

	public function set( $key, $data ) {

		$this->_data[ $key ] = $data;
	}
}
