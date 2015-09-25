<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Utility;



/**
 *	An utility class to manipulate data sets.
 *
 *	@package Essence.Utility
 */

class Hash {

	/**
	 *	Reindexes an array, according to the given correspondances.
	 *
	 *	@param array $data The data to be reindexed.
	 *	@param array $correspondances An array of index correspondances of the
	 *		form `array( 'currentIndex' => 'newIndex' )`.
	 *	@return array Reindexed array.
	 */

	public static function reindex( array $data, array $correspondances ) {

		$result = $data;

		foreach ( $correspondances as $from => $to ) {
			if ( isset( $data[ $from ])) {
				$result[ $to ] = $data[ $from ];
			}
		}

		return $result;
	}



	/**
	 *	Every element that is numerically indexed becomes a key, given
	 *	$default as value.
	 *
	 *	@param array $data The array to normalize.
	 *	@param mixed $default Default value.
	 *	@return array The normalized array.
	 */

	public static function normalize( array $data, $default ) {

		$normalized = [ ];

		foreach ( $data as $key => $value ) {
			if ( is_numeric( $key )) {
				$key = $value;
				$value = $default;
			}

			$normalized[ $key ] = $value;
		}

		return $normalized;
	}
}
