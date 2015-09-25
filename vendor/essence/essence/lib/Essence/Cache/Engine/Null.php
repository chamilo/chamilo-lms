<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Cache\Engine;

use Essence\Cache\Engine;



/**
 *	Does absolutely nothing.
 *
 *	@package Essence.Cache.Engine
 */

class Null implements Engine {

	/**
	 *	{@inheritDoc}
	 */

	public function has( $key ) {

		return false;
	}



	/**
	 *	{@inheritDoc}
	 */

	public function get( $key, $default = null ) {

		return $default;
	}



	/**
	 *	{@inheritDoc}
	 */

	public function set( $key, $data ) { }

}
