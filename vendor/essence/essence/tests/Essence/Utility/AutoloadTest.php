<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Utility;

use PHPUnit_Framework_TestCase;



/**
 *	Test case for Autoload.
 */

class AutoloadTest extends PHPUnit_Framework_TestCase {

	/**
	 *
	 */

	public function testSetup( ) {

		$this->assertTrue( class_exists( '\\Essence\\Provider\\OEmbed' ));
	}
}
