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
 * PENSPackageHandler
 * 
 * Provides the PENSPackageHandler abstract class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once __DIR__.'/pens_config.php';

/**
 * PENSPackageHandler
 * 
 * This class is an abstract class used to handle the processing of the package on the server side. Create a class that inherits from
 * this class and implements the processPackage method, then set the package handler of the PENSServer class using the instance newly created
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
abstract class PENSPackageHandler {
	/**
	 * Array of supported package types for this handler. Set by default to PENSConfig::$allowed_package_types
	 * @var array
	 */
	protected $_supported_package_types = null;
	
	/**
	 * Array of supported package formats for this handler. Set by default to PENSConfig::$allowed_package_formats
	 * @var array
	 */
	protected $_supported_package_formats = null;
	
	/**
	 * Constructor. Sets the default values of supported_package_types and supported_package_formats
	 */
	public function __construct() {
		$this->_supported_package_types = PENSConfig::$allowed_package_types;
		$this->_supported_package_formats = PENSConfig::$allowed_package_formats;
	}
	
	public function getSupportedPackageTypes() {
		return $this->_supported_package_types;
	}
	
	/**
	 * Sets the supported package types. All package types in the array provided as an argument
	 * not present in PENSConfig::$allowed_package_types will be ignored. The supported_package_types
	 * will be set to null if the argument given is not an array
	 * 
	 * @param array Supported package types
	 */
	public function setSupportedPackageTypes($supported_package_types) {
		$this->_supported_package_types = null;
		if(is_array($supported_package_types)) {
			foreach($supported_package_types as $package_type) {
				if(in_array($package_type, PENSConfig::$allowed_package_types)) {
					$this->_supported_package_types[] = $package_type;
				}
			}
		}
	}
	
	public function getSupportedPackageFormats() {
		return $this->_supported_package_formats;
	}
	
	/**
	 * Sets the supported package formats. All package types in the array provided as an argument
	 * not present in PENSConfig::$allowed_package_formats will be ignored. The supported_package_formats
	 * will be set to null if the argument given is not an array.
	 * 
	 * @param array Supported package formats
	 */
	public function setSupportedPackageFormats($supported_package_formats) {
		$this->_supported_package_formats = null;
		if(is_array($supported_package_formats)) {
			foreach($supported_package_formats as $package_format) {
				if(in_array($package_format, PENSConfig::$allowed_package_formats)) {
					$this->_supported_package_formats[] = $package_format;
				}
			}
		}
	}
	
	/**
	 * Processes the package. Must be implemented by subclasses
	 * 
	 * @param PENSRequestCollect Collect request sent by the client
	 * @param string Path to the collected package on the hard drive
	 */
	abstract public function processPackage($request, $path_to_package);
	
}
