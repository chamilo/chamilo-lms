<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Media\Preparator;

use PHPUnit_Framework_TestCase;
use Essence\Media;



/**
 *	Test case for Youtube.
 */

class YoutubeTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $Youtube = null;
	public $Media = null;



	/**
	 *
	 */

	public function setup( ) {

		$this->Media = new Media([
			'thumbnailUrl' => 'http://i1.ytimg.com/vi/r0dBPI4etvI/hqdefault.jpg'
		]);

		$this->Youtube = new Youtube( );
	}



	/**
	 *
	 */

	public function testCompleteWithSmallThumbnailUrl( ) {

		$this->Youtube->complete( $this->Media, [
			'thumbnailFormat' => 'small'
		]);

		$this->assertEquals(
			'http://i1.ytimg.com/vi/r0dBPI4etvI/default.jpg',
			$this->Media->get( 'thumbnailUrl' )
		);
	}



	/**
	 *
	 */

	public function testCompleteWithMediumThumbnailUrl( ) {

		$this->Youtube->complete( $this->Media, [
			'thumbnailFormat' => 'medium'
		]);

		$this->assertEquals(
			'http://i1.ytimg.com/vi/r0dBPI4etvI/mqdefault.jpg',
			$this->Media->get( 'thumbnailUrl' )
		);
	}
}
