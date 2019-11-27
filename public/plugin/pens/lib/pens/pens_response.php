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
 * PENSResponse
 * 
 * Provides the PENSResponse class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once __DIR__ . '/pens_config.php';
require_once __DIR__ . '/pens_message.php';
require_once __DIR__ . '/pens_exception.php';

/**
 * PENSResponse
 * 
 * PENSResponse class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
 class PENSResponse extends PENSMessage {
	 /**
	  * Error code
	  * @var int
	  */
	 protected $_error = null;
	 
	 /**
	  * Descriptive text of the error
	  * @var string
	  */
	 protected $_error_text = null;
	 
	 /**
	  * Data linked to the error
	  * @var string
	  */
	 protected $_pens_data = null;
	 
	 /**
	  * Version
	  * @var string
	  */
	 protected $_pens_version = null;
	 
	 /**
	  * Constructor
	  * 
	  * @param mixed Can be a PENSException or an error code or a string representing an HTTP response
	  * @param string error text
	  * @param string PENS data
	  */
	public function __construct($error, $error_text = null, $pens_data = null) {
		$this->_pens_version = PENSConfig::$version;
		if($error instanceof PENSException) {
			$this->_error = $error->getCode();
			$this->_error_text = $error->getMessage();
		} else if(is_string($error)){
			// Parse the string
			$this->parseResponse($error);
		} else if(is_array($error)) {
			// Try to build from array
			$this->_error = $error["error"];
			$this->_error_text = $error["error-text"];
			$this->_pens_data = $error["pens-data"];
		} else {
			$this->_error = $error;
			$this->_error_text = $error_text;
			$this->_pens_data = $pens_data;
		}
		
	}
	
	/**
	 * Parses an HTTP response and assigns the attributes of the object
	 * 
	 * @param string HTTP response
	 */
	protected function parseResponse($response) {
		$lines = explode(PENSConfig::$eol, $response);
		$i = 1;
		foreach($lines as $line) {
			if($i < 5) {
				$pair = explode("=", $line);
				if($pair[0] == "error") {
					$this->_error = intval($pair[1]);
				} else if($pair[0] == "error-text") {
					$this->_error_text = $pair[1];
				} else if($pair[0] == "version") {
					$this->_pens_version = $pair[1];
				} else if($pair[0] == "pens-data") {
					if(!empty($pair[1])) {
						$this->_pens_data = $pair[1].PENSConfig::$eol;
					}
				}
			} else {
				if(!empty($line)) {
					$this->_pens_data .= $line.PENSConfig::$eol;
				}
			}
			$i++;
		}
	}
	
	public function getError() {
		return $this->_error;
	}
	
	public function getErrorText() {
		return $this->_error_text;
	}
	
	public function getPensData() {
		return $this->_pens_data;
	}
	
	public function getVersion() {
		return $this->_pens_version;
	}
	
	/**
	 * Sends the response content through HTTP headers
	 */
	public function send() {
		header("Content-Type: text/plain");
		print $this;
	}
	
	/**
	 * Returns an associative array of the response
	 * 
	 * @return array Associative array
	 */
	public function getArray() {
		return array("error" => $this->_error,
			"error-text" => $this->_error_text,
			"version" => PENSConfig::$version,
			"pens-data" => $this->_pens_data);
	}
	
	/**
	 * Transforms the object into a string
	 */
	public function __toString() {
		$eol = PENSConfig::$eol;
		$return = "error=".$this->_error.$eol;
		$return .= "error-text=".$this->_error_text.$eol;
		$return .= "version=".PENSConfig::$version.$eol;
		$return .= "pens-data=".$this->_pens_data.$eol;
		return $return;
	}
 
 }
 
