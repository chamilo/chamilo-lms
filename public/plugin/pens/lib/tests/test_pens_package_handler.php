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
 * Unit tests for the PENSPackageHandler class
 * 
 * This class provides unit tests for the PENSPackageHandler class
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once 'simpletest/autorun.php';
require_once __DIR__.'/../pens.php';

class MyPackageHandler extends PENSPackageHandler {
	public function processPackage($request, $path_to_package) {
	}
}

/**
 * Unit tests for the PENSPackageHandler class
 * 
 * This class provides unit tests for the PENSPackageHandler class
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class TestPENSPackageHandler extends UnitTestCase {
	
	public function testSupportedPackageTypesInvalid() {
		$object = new MyPackageHandler();
		$object->setSupportedPackageTypes("testing");
		$this->assertEqual($object->getSupportedPackageTypes(), null);
	}
	
	public function testSupportedPackageTypesInvalid2() {
		$object = new MyPackageHandler();
		$object->setSupportedPackageTypes(array("aicc-pkg", "testing"));
		$this->assertEqual($object->getSupportedPackageTypes(), array("aicc-pkg"));
	}
	
	public function testSupportedPackageTypesValid() {
		$object = new MyPackageHandler();
		$object->setSupportedPackageTypes(array("aicc-pkg", "lms-qti"));
		$this->assertEqual($object->getSupportedPackageTypes(), array("aicc-pkg", "lms-qti"));
	}
	
	public function testSupportedPackageFormatsInvalid() {
		$object = new MyPackageHandler();
		$object->setSupportedPackageFormats("testing");
		$this->assertEqual($object->getSupportedPackageFormats(), null);
	}
	
	public function testSupportedPackageFormatsInvalid2() {
		$object = new MyPackageHandler();
		$object->setSupportedPackageFormats(array("zip", "testing"));
		$this->assertEqual($object->getSupportedPackageFormats(), array("zip"));
	}
	
	public function testSupportedPackageFormatsValid() {
		$object = new MyPackageHandler();
		$object->setSupportedPackageFormats(array("zip", "jar"));
		$this->assertEqual($object->getSupportedPackageFormats(), array("zip", "jar"));
	}
}
