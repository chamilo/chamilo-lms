<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Http;



/**
 *	Handles HTTP related operations.
 *
 *	@package Essence.Http
 */

abstract class Client {

	/**
	 *	User agent.
	 *
	 *	@var string
	 */

	protected $_userAgent = '';



	/**
	 *	Retrieves contents from the given URL.
	 *
	 *	@param string $url The URL fo fetch contents from.
	 *	@return string The contents.
	 *	@throws Essence\Http\Exception
	 */

	abstract public function get( $url );



	/**
	 *	Sets the user agent for HTTP requests.
	 *
	 *	@param string $userAgent User agent.
	 */
	public function setUserAgent( $userAgent ) {

		$this->_userAgent = $userAgent;
	}
}
