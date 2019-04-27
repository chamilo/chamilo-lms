<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Http\Client;

use Essence\Http\Client;
use Essence\Http\Exception;



/**
 *	Handles HTTP related operations through file_get_contents( ).
 *
 *	@package Essence.Http.Client
 */

class Native extends Client {

	/**
	 *	Default HTTP status code.
	 *
	 *	@var int
	 */

	protected $_defaultCode;



	/**
	 *	Constructor.
	 *
	 *	@param int $defaultCode The default HTTP status code to assume if
	 *		response headers cannot be parsed.
	 */

	public function __construct( $defaultCode = 404 ) {

		$this->_defaultCode = $defaultCode;
	}



	/**
	 *	Retrieves contents from the given URL.
	 *	Thanks to Diije for the hint on $http_response_header
	 *	(http://www.felix-girault.fr/astuces/recuperer-une-page-web-en-php/#comment-1029).
	 *
	 *	@param string $url The URL fo fetch contents from.
	 *	@return string The fetched contents.
	 *	@throws Essence\Http\Exception
	 */

	public function get( $url ) {

		$options = [
			'http' => [
				'user_agent' => $this->_userAgent
			]
		];

		$context = stream_context_create( $options );
		$reporting = error_reporting( 0 );
		$contents = file_get_contents( $url, false, $context );

		error_reporting( $reporting );

		if ( $contents === false ) {
			$code = $this->_defaultCode;

			if ( isset( $http_response_header )) {
				preg_match(
					'#^HTTP/[0-9\.]+\s(?P<code>[0-9]+)#i',
					$http_response_header[ 0 ],
					$matches
				);

				if ( isset( $matches['code'])) {
					$code = $matches['code'];
				}
			}

			// let's assume the file doesn't exists
			throw new Exception( $url, $code );
		}

		return $contents;
	}
}
