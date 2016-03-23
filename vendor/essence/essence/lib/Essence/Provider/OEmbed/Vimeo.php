<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Provider\OEmbed;

use Essence\Provider\OEmbed;



/**
 *
 *
 *	@package Essence.Provider.OEmbed
 */

class Vimeo extends OEmbed {

	/**
	 *	Refactors URLs like these:
	 *	- http://player.vimeo.com/video/20830433
	 *
	 *	in such form:
	 *	- http://www.vimeo.com/20830433
	 *
	 *	@param string $url Url to prepare.
	 *	@return string Prepared url.
	 */

	public static function prepareUrl( $url, array $options = [ ]) {

		$url = parent::prepareUrl( $url );

		if ( preg_match( '#player\.vimeo\.com/video/(?<id>[0-9]+)#i', $url, $matches )) {
			$url = 'http://www.vimeo.com/' . $matches['id'];
		}

		return $url;
	}
}
