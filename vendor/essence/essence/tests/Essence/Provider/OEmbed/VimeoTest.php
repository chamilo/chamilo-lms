<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Provider\OEmbed;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Vimeo.
 */

class VimeoTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public function testPrepareUrl( ) {

		$this->assertEquals(
			'http://www.vimeo.com/20830433',
			Vimeo::prepareUrl( 'http://player.vimeo.com/video/20830433' )
		);
	}
}
