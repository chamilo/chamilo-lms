<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Cache\Engine;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Volatile.
 */

class VolatileTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $Volatile = null;



	/**
	 *
	 */

	public function setUp( ) {

		$this->Volatile = new Volatile( );
	}



	/**
	 *
	 */

	public function testHas( ) {

		$this->assertFalse( $this->Volatile->has( 'key' ));

		$this->Volatile->set( 'key', 'value' );
		$this->assertTrue( $this->Volatile->has( 'key' ));
	}



	/**
	 *
	 */

	public function testGetSet( ) {

		$this->assertNull( $this->Volatile->get( 'key' ));
		$this->assertEquals( 'value', $this->Volatile->get( 'key', 'value' ));

		$this->Volatile->set( 'key', 'value' );
		$this->assertEquals( 'value', $this->Volatile->get( 'key' ));
	}
}
