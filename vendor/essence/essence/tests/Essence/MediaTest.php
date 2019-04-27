<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Media.
 */

class MediaTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $Media = null;



	/**
	 *
	 */

	public function setUp( ) {

		$this->Media = new Media( [
			'property' => 'value'
		]);
	}



	/**
	 *
	 */

	public function testConstruct( ) {

		$this->assertTrue( $this->Media->has( 'property' ));
	}



	/**
	 *
	 */

	public function testIterator( ) {

		foreach ( $this->Media as $property => $value ) { }
	}



	/**
	 *
	 */

	public function testSerialize( ) {

		$this->assertEquals(
			json_encode( $this->Media->properties( )),
			json_encode( $this->Media )
		);
	}
}
