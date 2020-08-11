<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Media\Preparator;

use Essence\Media\Preparator;
use Essence\Media;



/**
 *	Handles custom thumbnail formats.
 *
 *	@package Essence.Media.Preparator
 */

class Youtube extends Preparator {

	/**
	 *	{@inheritDoc}
	 *
	 *	@param array $options Embed options.
	 *		- 'thumbnailFormat' string
	 */

	public function complete( Media $Media, array $options = [ ]) {

		parent::complete( $Media, $options );

		if ( isset( $options['thumbnailFormat'])) {
			$url = $Media->get( 'thumbnailUrl' );

			switch ( $options['thumbnailFormat']) {
				case 'small':
					$url = str_replace( 'hqdefault', 'default', $url );
					break;

				case 'medium':
					$url = str_replace( 'hqdefault', 'mqdefault', $url );
					break;

				// CAUTION!
				// this thumbnail format might not be available for all videos
				case 'max':
					$url = str_replace( 'hqdefault', 'maxresdefault', $url );
					break;

				case 'large':
				default:
					// unchanged
					break;
			}

			$Media->set( 'thumbnailUrl', $url );
		}
	}
}
