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
	 *	@dataProvider thumbnailFormatProvider
	 */

	public function testCompleteWithThumbnailUrl( $format, $file ) {

		$this->Youtube->complete( $this->Media, [
			'thumbnailFormat' => $format
		]);

		$this->assertEquals(
			"http://i1.ytimg.com/vi/r0dBPI4etvI/$file.jpg",
			$this->Media->get( 'thumbnailUrl' )
		);
	}



	/**
	 *
	 */

	public function thumbnailFormatProvider( ) {
		return [
			['small', 'default'],
			['medium', 'mqdefault'],
			['large', 'hqdefault'],
			['max', 'maxresdefault']
		];
	}
}
