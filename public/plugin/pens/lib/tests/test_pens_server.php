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
 * Functional tests for the PENS Server
 * 
 * This class provides functional testing of the PENS Server
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
require_once("simpletest/autorun.php");
require_once("simpletest/web_tester.php");

define(PENS_TEST_SERVER_URL, "http://localhost/pens/pens_server_test.php");
define(PENS_TEST_RECEIPT_MAILTO, "mailto:test@test.com");

/**
 * Functional tests for the PENS Server
 * 
 * This class provides functional tests for the PENS Server
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class TestPENSServer extends WebTestCase {
	
	private $_valid_params = array(
		"command" => "collect",
		"pens-version" => "1.0.0",
		"package-type" => "aicc-pkg",
		"package-type-version" => "1.0",
		"package-format" => "zip",
		"package-id" => "http://myurl.com/12345",
		"package-url" => "http://www.aicc.org/pens/sample/captivatequiz.zip",
		"package-url-expiry" => "2099-04-09T14:17:43Z",
		"client" => "test-pens-server",
		"receipt" => PENS_TEST_RECEIPT_MAILTO);
		
	public function testEmptyQuery() {
		$this->get(PENS_TEST_SERVER_URL);
		$this->assertText("error=2002");
	}
	
	public function testValidQuery() {
		$this->get(PENS_TEST_SERVER_URL, $this->_valid_params);
		$this->assertText("error=0");
	}
	
	private function invalidParameterTest($name, $value, $error) {
		$params = $this->_valid_params;
		$params[$name] = $value;
		$this->get(PENS_TEST_SERVER_URL, $params);
		$this->assertText("error=".$error);
	}
	
	private function validParameterTest($name, $value) {
		$params = $this->_valid_params;
		$params[$name] = $value;
		$this->get(PENS_TEST_SERVER_URL, $params);
		$this->assertText("error=0");
	}
	
	public function testInvalidCommand() {
		$this->invalidParameterTest("command", "invalid", 2002);
	}
	
	public function testInvalidVersion() {
		$this->invalidParameterTest("pens-version", "0.8.0", 2001);
	}
	
	public function testInvalidPackageType() {
		$this->invalidParameterTest("package-type", "invalid", 2003);
	}
	
	public function testEmptyPackageTypeVersion() {
		$this->invalidParameterTest("package-type-version", null, 2004);
	}
	
	public function testInvalidPackageFormat() {
		$this->invalidParameterTest("package-format", "invalid", 2005);
	}
	
	public function testInvalidPackageId() {
		$this->invalidParameterTest("package-id", "invalid", 2007);
	}
	
	public function testInvalidPackageUrl() {
		$this->invalidParameterTest("package-url", "invalid", 2008);
	}
	
	public function testInvalidPackageUrlExpiry() {
		$this->invalidParameterTest("package-url-expiry", "invalid", 2009);
	}
	
	public function testValidPackageUrlExpiry() {
		$this->validParameterTest("package-url-expiry", "2099-01-01");
	}
	
}

