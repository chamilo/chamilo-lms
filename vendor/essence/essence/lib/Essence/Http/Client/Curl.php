<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Http\Client;

use Essence\Http\Client;
use Essence\Http\Exception;



/**
 *	Handles HTTP related operations through cURL.
 *
 *	@package Essence.Http.Client
 */

class Curl extends Client {

	/**
	 *	CURL handle.
	 *
	 *	@var resource
	 */

	protected $_curl = null;



	/**
	 *	Default cURL options, takes precedence over the user options.
	 *
	 *	@var array
	 */

	protected $_defaults = [
		CURLOPT_HEADER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true
	];



	/**
	 *	Initializes cURL with the given options.
	 *
	 *	@param array cURL options.
	 */

	public function __construct( array $options = [ ]) {

		$this->_curl = curl_init( );

		curl_setopt_array(
			$this->_curl,
			$this->_defaults + $options
		);
	}



	/**
	 *	Closes cURL connexion.
	 */

	public function __destruct( ) {

		curl_close( $this->_curl );
	}



	/**
	 *	{@inheritDoc}
	 */

	public function get( $url ) {

		curl_setopt( $this->_curl, CURLOPT_URL, $url );
		curl_setopt( $this->_curl, CURLOPT_USERAGENT, $this->_userAgent );

		$contents = curl_exec( $this->_curl );

		if ( $contents === false ) {
			throw new Exception(
				$url,
				curl_getinfo( $this->_curl, CURLINFO_HTTP_CODE )
			);
		}

		return $contents;
	}
}
