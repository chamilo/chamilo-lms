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
 * PENSRequestHandler
 * 
 * Provides the PENSRequestHandler abstract class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

/**
 * PENSRequestHandler
 * 
 * This class is an abstract class used to handle the processing of the requests on the client side. Create a class that inherits from
 * this class and implements the processRequest method, then set the request handler of the PENSClient object.
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
abstract class PENSRequestHandler {
	/**
	 * Processes the request. Must be implemented by subclasses
	 * 
	 * @param PENSRequest Alert or Receipt request sent by the server
	 * @param PENSResponse Response sent by the server (error, error-text etc...)
	 */
	abstract public function processRequest($request, $response);
}

