<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Media;

use Essence\Media;



/**
 *
 *
 *	@package Essence.Media
 */

class Preparator {

	/**
	 *
	 */

	protected $_defaults = [
		'width' => 640,
		'height' => 490
	];



	/**
	 *	Builds an HTML code from the given media's properties to fill its
	 *	'html' property.
	 *
	 *	@param Essence\Media $Media A reference to the Media.
	 *	@param array $options Options.
	 */

	public function complete( Media $Media, array $options = [ ]) {

		if ( $Media->has( 'html' )) {
			return;
		}

		$title = htmlspecialchars( $Media->get( 'title', $Media->url ));
		$description = $Media->has( 'description' )
			? htmlspecialchars( $Media->description )
			: $title;

		$options += $this->_defaults;
		$width = $Media->setDefault( 'width', $options['width']);
		$height = $Media->setDefault( 'height', $options['height']);

		switch ( $Media->type ) {
			// builds an <img> tag pointing to the photo
			case 'photo':
				$Media->set( 'html', sprintf(
					'<img src="%s" alt="%s" width="%d" height="%d" />',
					$Media->url,
					$description,
					$width,
					$height
				));
				break;

			// builds an <iframe> tag pointing to the video
			case 'video':
				$Media->set( 'html', sprintf(
					'<iframe src="%s" width="%d" height="%d" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen />',
					$Media->url,
					$width,
					$height
				));
				break;

			// builds an <a> tag pointing to the original resource
			default:
				$Media->set( 'html', sprintf(
					'<a href="%s" alt="%s">%s</a>',
					$Media->url,
					$description,
					$title
				));
				break;
		}
	}
}
