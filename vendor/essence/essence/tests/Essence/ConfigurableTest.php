<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence;

use PHPUnit_Framework_TestCase;



/**
 *
 */

class ConfigurableImplementation {

	use Configurable;



	/**
	 *
	 */

	protected $_properties = [
		'one' => 1,
		'two' => 2
	];
}



/**
 *	Test case for Configurable.
 */

class ConfigurableTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public $Configurable = null;



	/**
	 *
	 */

	public function setUp( ) {

		$this->Configurable = new ConfigurableImplementation( );
	}



	/**
	 *
	 */

	public function testMagicIsSet( ) {

		$this->assertTrue( isset( $this->Configurable->one ));
		$this->assertFalse( isset( $this->Configurable->unset ));
	}



	/**
	 *
	 */

	public function testMagicGet( ) {

		$this->assertEquals( 1, $this->Configurable->one );
	}



	/**
	 *
	 */

	public function testMagicSet( ) {

		$this->Configurable->foo = 'bar';
		$this->assertEquals( 'bar', $this->Configurable->foo );
	}



	/**
	 *
	 */

	public function testHas( ) {

		$this->assertTrue( $this->Configurable->has( 'one' ));
		$this->assertFalse( $this->Configurable->has( 'unset' ));
	}



	/**
	 *
	 */

	public function testGet( ) {

		$this->assertEquals( 1, $this->Configurable->get( 'one' ));
		$this->assertEmpty( $this->Configurable->get( 'unset' ));
	}



	/**
	 *
	 */

	public function testSet( ) {

		$this->Configurable->set( 'foo', 'bar' );
		$this->assertEquals( 'bar', $this->Configurable->foo );
	}



	/**
	 *
	 */

	public function testSetDefault( ) {

		$this->Configurable->setDefault( 'one', 2 );
		$this->assertEquals( 1, $this->Configurable->get( 'one' ));

		$this->Configurable->setDefault( 'three', 3 );
		$this->assertEquals( 3, $this->Configurable->get( 'three' ));
	}



	/**
	 *
	 */

	public function testSetDefaults( ) {

		$this->Configurable->setDefaults([
			'one' => 2,
			'three' => 3
		]);

		$this->assertEquals( 1, $this->Configurable->get( 'one' ));
		$this->assertEquals( 3, $this->Configurable->get( 'three' ));
	}
}
