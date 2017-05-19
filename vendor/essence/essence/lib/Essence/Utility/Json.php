<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Utility;

use Essence\Exception;



/**
 *	A simple JSON parser.
 *
 *	@package Essence.Utility
 */

class Json {

	/**
	 *	JSON error messages.
	 *
	 *	@var array
	 */

	protected static $_errors = [
		JSON_ERROR_NONE => 'no error',
		JSON_ERROR_DEPTH => 'depth error',
		JSON_ERROR_STATE_MISMATCH => 'state mismatch error',
		JSON_ERROR_CTRL_CHAR => 'control character error',
		JSON_ERROR_SYNTAX => 'syntax error',
		JSON_ERROR_UTF8 => 'UTF-8 error'
	];



	/**
	 *	Parses a JSON document and returns an array of data.
	 *
	 *	@param string $json JSON document.
	 *	@return array Data.
	 */

	public static function parse( $json ) {

		$data = json_decode( $json, true );

		if ( $data === null ) {
			throw new Exception(
				'Error parsing JSON response: '
				. self::$_errors[ json_last_error( )]
				. '.'
			);
		}

		return $data;
	}
}
