<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Provider\OEmbed;

use Essence\Media;
use Essence\Provider\OEmbed;



/**
 *
 *	@package Essence.Provider.OEmbed
 */

class Youtube extends OEmbed {

	/**
	 *	Refactors URLs like these:
	 *	- http://www.youtube.com/watch?v=oHg5SJYRHA0&noise=noise
	 *	- http://www.youtube.com/v/oHg5SJYRHA0
	 *	- http://www.youtube.com/embed/oHg5SJYRHA0
	 *	- http://youtu.be/oHg5SJYRHA0
	 *
	 *	in such form:
	 *	- http://www.youtube.com/watch?v=oHg5SJYRHA0
	 *
	 *	@param string $url Url to prepare.
	 *	@return string Prepared url.
	 */

	public static function prepareUrl( $url, array $options = [ ]) {

		$url = trim( $url );

		if ( preg_match( '#(?:v=|v/|embed/|youtu\.be/)(?<id>[a-z0-9_-]+)#i', $url, $matches )) {
			$url = 'http://www.youtube.com/watch?v=' . $matches['id'];
		}

		return $url;
	}
}
