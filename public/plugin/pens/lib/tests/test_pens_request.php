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
 * Unit tests for the PENSRequest class
 * 
 * This class provides unit tests for the PENSRequest class
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once 'simpletest/autorun.php';
require_once __DIR__.'/../pens.php';

/**
 * Unit tests for the PENSRequest class
 * 
 * This class provides unit tests for the PENSRequest class
 * 
 * @package PENS
 * @subpackage Tests
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class TestPENSRequest extends UnitTestCase {
	
	/**
	 * Valid arguments to be used for tests
	 * 
	 * @var array
	 */
	private $args = null;
	
	public function setUp() {
		$this->args = array(
			"pens-version" => "1.0.0",
			"command" => "collect",
			"package-type" => "aicc-pkg",
			"package-type-version" => "1.0",
			"package-format" => "zip",
			"package-id" => "http://myurl.com/12345",
			"package-url" => "http://myurl.com/mypackage.zip",
			"package-url-expiry" => "2006-04-01T06:51:29Z",
			"client" => "Authorware7",
			"receipt" => "mailto:name@domain.com",
			"package-url-user-id" => "guillaumev",
			"package-url-account" => "toto",
			"package-url-password" => "12345",
			"system-user-id" => "guillaumev",
			"system-password" => "12345",
			"alerts" => "http://myurl.com/alerts",
			"vendor-data" => "here are my data");
	}
	
	public function createObject($myargs) {
		return PENSRequestFactory::createPENSRequest($myargs);
	}
	
	public function exceptionTestForValue($key, $value, $code) {
		try {
			$myargs = $this->args;
			if($value === null) {
				unset($myargs[$key]);
			} else {
				$myargs[$key] = $value;
			}
			$object = $this->createObject($myargs);
			$this->fail();
		} catch(PENSException $e) {
			$this->assertEqual($e->getCode(), $code);
		}
	}
	
	public function testPensVersionInvalid() {
		$this->exceptionTestForValue("pens-version", "0.8.0", 2001);
	}
	
	public function testPensVersionNull() {
		$this->exceptionTestForValue("pens-version", null, 2001);
	}
	
	
	public function testCommandInvalid() {
		$this->exceptionTestForValue("command", "testing", 2002);
	}
	
	public function testCommandNull() {
		$this->exceptionTestForValue("command", null, 2002);
	}
	
	public function testPackageTypeInvalid() {
		$this->exceptionTestForValue("package-type", "testing", 2003);
	}
	
	public function testPackageTypeNull() {
		$this->exceptionTestForValue("package-type", null, 2003);
	}
	
	public function testPackageTypeVersionNull() {
		$this->exceptionTestForValue("package-type-version", null, 2004);
	}
	
	public function testPackageFormatInvalid() {
		$this->exceptionTestForValue("package-format", "testing", 2005);
	}
	
	public function testPackageFormatNull() {
		$this->exceptionTestForValue("package-format", null, 2005);
	}
	
	public function testPackageIdInvalid() {
		$this->exceptionTestForValue("package-id", "testing", 2007);
	}
	
	public function testPackageIdNull() {
		$this->exceptionTestForValue("package-id", null, 2007);
	}
	
	public function testPackageUrlInvalid() {
		$this->exceptionTestForValue("package-url", "testing", 2008);
	}
	
	public function testPackageUrlInvalid2() {
		$this->exceptionTestForValue("package-url", "http://myurl.com/mypackage", 2008);
	}
	
	public function testPackageUrlNull() {
		$this->exceptionTestForValue("package-url", null, 2008);
	}
	
	public function testPackageUrlExpiryNull() {
		$this->exceptionTestForValue("package-url-expiry", null, 2009);
	}
	
	public function testPackageUrlExpiryInvalid() {
		$this->exceptionTestForValue("package-url-expiry", "testing", 2009);
	}
	
	public function testClientNull() {
		$this->exceptionTestForValue("client", null, 2010);
	}
	
	public function testReceiptNull() {
		$this->exceptionTestForValue("receipt", null, 2011);
	}
	
	public function testReceiptInvalid() {
		$this->exceptionTestForValue("receipt", "testing", 2011);
	}
	
	public function testAlertsInvalid() {
		$this->exceptionTestForValue("alerts", "testing", 1201);
	}
	
	public function testReceiptValid() {
		try {
			$myargs = $this->args;
			$myargs["receipt"] = "mailto:name@domain.com,name2@domain.com";
			$object = $this->createObject($myargs);
			$this->pass();
		} catch(PENSException $e) {
			$this->fail();
		}
	}
	
	public function testValid() {
		try {
			$object = $this->createObject($this->args);
			$this->assertIsA($object, "PENSRequestCollect");
			$this->assertEqual($object->getPensVersion(), "1.0.0");
			$this->assertEqual($object->getPackageType(), "aicc-pkg");
			$this->assertEqual($object->getPackageTypeVersion(), "1.0");
			$this->assertEqual($object->getPackageFormat(), "zip");
			$this->assertEqual($object->getPackageId(), "http://myurl.com/12345");
			$this->assertEqual($object->getPackageUrl(), "http://myurl.com/mypackage.zip");
			$this->assertIsA($object->getPackageUrlExpiry(), "DateTime");
			$this->assertEqual($object->getClient(), "Authorware7");
			$this->assertEqual($object->getReceipt(), "mailto:name@domain.com");
			$this->assertEqual($object->getPackageUrlUserId(), "guillaumev");
			$this->assertEqual($object->getPackageUrlAccount(), "toto");
			$this->assertEqual($object->getPackageUrlPassword(), "12345");
			$this->assertEqual($object->getSystemUserId(), "guillaumev");
			$this->assertEqual($object->getSystemPassword(), "12345");
			$this->assertEqual($object->getAlerts(), "http://myurl.com/alerts");
			$this->assertEqual($object->getVendorData(), "here are my data");
			
		} catch(PENSException $e) {
			$this->fail();
		}
	}
	
	
}
