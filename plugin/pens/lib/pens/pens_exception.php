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
 * PENSException
 * 
 * Provides the PENSException class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

/**
 * PENSException
 * 
 * Class extending the PHP Exception class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class PENSException extends Exception {

	/**
	 * Array that provides an association between exception codes and messages
	 * @var array
	 */
	protected static $_code_to_messages = array(
		1101 => "Unable to parse PENS command",
		1201 => "Attempt to pass an invalid argument",
		1301 => "Unable to retrieve package",
		1302 => "Unable to retrieve package via HTTPS",
		1304 => "Unable to retrieve package via FTP",
		1306 => "Unable to retrieve package via FTPS",
		1310 => "Unable to retrieve package at specified URL due to error in URL or lack of response from URL",
		1312 => "Unable to retrieve package at specified URL due to error with access credential for package URL",
		1320 => "Expiration date is non-null and in an improper format",
		1322 => "Current time indicates expiry date has passed",
		1420 => "PENS version not supported",
		1421 => "Command not supported",
		1430 => "Package type not supported",
		// The following error code is not specified in the PENS specification and was added in this implementation
		1431 => "Package format not supported",
		1432 => "Internal package error",
		1440 => "Insufficient host space/storage available",
		1500 => "Unable to communicate with provided acknowledgement URL",
		1510 => "Unsupported acknowledgement protocol",
		1520 => "Unsupported alert protocol",
		2001 => "PENS version invalid or not specified",
		2002 => "PENS command invalid or not specified",
		2003 => "package-type invalid or not specified",
		2004 => "package-type-version invalid or not specified",
		2005 => "package-format invalid or not specified",
		2007 => "package-id invalid or not specified",
		2008 => "package-url invalid or not specified",
		2009 => "package-url-expiry date invalid or not specified",
		2010 => "client submitting package invalid or not specified",
		2011 => "receipt url invalid or not specified"
	);
	
	/**
	 * Redefines the constructor so that code is the first argument
	 * 
	 * @param int Exception code
	 * @param string Message to display
	 * @return PENSException Exception created
	 */
	public function __construct($code, $message = null) {
		parent::__construct($message, $code);
		$this->setMessageBasedOnCode();
	}
	
	/**
	 * Sets the message based on the code
	 */
	protected function setMessageBasedOnCode() {
		if(empty($this->message) && !empty(self::$_code_to_messages[$this->code])) {
			$this->message = self::$_code_to_messages[$this->code];
		}
	}
	
}
