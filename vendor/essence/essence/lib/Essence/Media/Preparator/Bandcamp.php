<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Media\Preparator;

use Essence\Media\Preparator;
use Essence\Media;



/**
 *	Builds an HTML code for the Bandcamp player.
 *
 *	@package Essence.Media.Preparator
 */

class Bandcamp extends Preparator {

	/**
	 *	{@inheritDoc}
	 */

	public function complete( Media $Media, array $options = [ ]) {

		parent::complete( $Media, $options );

		if (
			$Media->has( 'og:video' )
			&& ( $Media->get( 'og:video:type') === 'application/x-shockwave-flash' )
			&& preg_match( '/((album|track)=\d+)/', $Media->get( 'og:video' ), $matches )
		) {
			$url = htmlspecialchars( $Media->get( 'url' ));
			$title = htmlspecialchars( $Media->get( 'title' ));
			$height = ( $matches[ 2 ] == 'album' )
				? 470
				: 442;

			$Media->set( 'html:small', '<iframe style="border: 0; width: 100%; height: 42px;" src="http://bandcamp.com/EmbeddedPlayer/' . $matches[ 1 ] . '/size=small/bgcol=ffffff/linkcol=0687f5/transparent=true/" seamless><a href="' . htmlspecialchars($Media->get('url')) . '">' . $title . '</a></iframe>' );
			$Media->set( 'html:medium', '<iframe style="border: 0; width: 100%; height: 120px;" sr="http://bandcamp.com/EmbeddedPlayer/' . $matches[ 1 ] . '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/artwork=small/transparent=true/" seamless><a href="' . $url . '">' . $title . '</a></iframe>' );
			$Media->set( 'html:large', '<iframe style="border: 0; width: 350px; height: ' . $height . 'px;" src="http://bandcamp.com/EmbeddedPlayer/' . $matches[ 1 ] . '/size=large/bgcol=ffffff/linkcol=0687f5/tracklist=false/transparent=true/" seamless><a href="' . $url . '">' . $title . '</a></iframe>' );

			$Media->set('html', $Media->get( 'html:small' ));
		}
	}
}
