<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Media\Preparator;

use Essence\Media\Preparator;
use Essence\Media;



/**
 *	Builds an HTML code for the Vine player.
 *
 *	@package Essence.Media.Preparator
 */

class Vine extends Preparator {

	/**
	 *	{@inheritDoc}
	 */

	public function complete( Media $Media, array $options = [ ]) {

		parent::complete( $Media, $options );

		if (
			( $Media->get( 'type' ) === 'vine-app:video' )
			&& preg_match( '#https?://vine.co/v/[a-zA-Z0-9]+#i', $Media->get( 'url' ), $matches )
		) {
			$Media->set( 'html:small', '<iframe class="vine-embed" src="' . $matches[ 0 ] . '/embed/postcard" width="320" height="320" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>' );
			$Media->set( 'html:medium', '<iframe class="vine-embed" src="' . $matches[ 0 ] . '/embed/postcard" width="480" height="480" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>' );
			$Media->set( 'html:large', '<iframe class="vine-embed" src="' . $matches[ 0 ] . '/embed/postcard" width="600" height="600" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>' );

			$Media->set( 'html', $Media->get( 'html:small' ));
		}
	}
}
