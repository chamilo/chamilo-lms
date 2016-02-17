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
 * PENSConfig
 * 
 * Provides the PENSConfig class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
 
 /**
 * PENSConfig
 * 
 * This class provides different static variables to configure php-pens.
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class PENSConfig {

	/**
	 * PENS version
	 * @var string
	 */
	public static $version = "1.0.0";
	
	/**
	 * Allowed commands
	 * @var array
	 */
	public static $allowed_commands = array("collect", "receipt", "alert");
	
	/**
	 * Allowed package types
	 * @var array
	 */
	public static $allowed_package_types = array("aicc-pkg", "scorm-pif", "ims-qti");
	
	/**
	 * Allowed package formats
	 * @var array
	 */
	public static $allowed_package_formats = array("zip", "url", "jar", "war", "xml");
	
	/**
	 * End of line as specified by the PENS specification
	 * @var string
	 */
	public static $eol = "\r\n";
}
