<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Provider\OEmbed;

use PHPUnit_Framework_TestCase;
use Essence\Media;



/**
 *	Test case for Youtube.
 */

class YoutubeTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public function testPrepareUrl( ) {

		$this->assertEquals(
			'http://www.youtube.com/watch?v=oHg5SJYRHA0',
			Youtube::prepareUrl( 'http://www.youtube.com/watch?v=oHg5SJYRHA0&noise=noise' )
		);

		$this->assertEquals(
			'http://www.youtube.com/watch?v=oHg5SJYRHA0',
			Youtube::prepareUrl( 'http://www.youtube.com/v/oHg5SJYRHA0' )
		);

		$this->assertEquals(
			'http://www.youtube.com/watch?v=oHg5SJYRHA0',
			Youtube::prepareUrl( 'http://www.youtube.com/embed/oHg5SJYRHA0' )
		);

		$this->assertEquals(
			'http://www.youtube.com/watch?v=oHg5SJYRHA0',
			Youtube::prepareUrl( 'http://youtu.be/oHg5SJYRHA0' )
		);
	}
}
