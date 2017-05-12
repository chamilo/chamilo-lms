<?php

/**
 * This file is part of php-pens.
 * 
 * php-pens is free software: you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * php-pens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with php-pens.  If not, see <http://www.gnu.org/licenses/>.
 */ 


/**
 * Unit tests for the PENSResponse class
 * 
 * This class provides unit tests for the PENSResponse class
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once 'simpletest/autorun.php';
require_once __DIR__.'/../pens.php';

/**
 * Unit tests for the PENSResponse class
 * 
 * This class provides unit tests for the PENSResponse class
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class TestPENSResponse extends UnitTestCase {
	
	public function testCreationFromPENSException() {
		$object = new PENSResponse(new PENSException(1101));
		$this->assertEqual($object->getError(), 1101);
		$this->assertNotNull($object->getErrorText());
	}
	
	public function testCreationFromArguments() {
		$object = new PENSResponse(0, "collect command received and understood");
		$this->assertIdentical($object->getError(), 0);
		$this->assertEqual($object->getErrorText(), "collect command received and understood");
	}
	
	public function testCreationFromHTTPResponse() {
		$eol = PENSConfig::$eol;
		$response = "error=1101".$eol."error-text=unable to parse PENS command".$eol."version=1.0.0".$eol."pens-data=".$eol;
		$object = new PENSResponse($response);
		$this->assertIdentical($object->getError(), 1101);
		$this->assertIdentical($object->getErrorText(), "unable to parse PENS command");
		$this->assertIdentical($object->getVersion(), "1.0.0");
		$this->assertNull($object->getPensData());
	}
}
