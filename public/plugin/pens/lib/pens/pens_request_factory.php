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
 * PENSRequestFactory
 * 
 * Provides the PENSRequestFactory class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once __DIR__ . '/pens_exception.php';
require_once __DIR__ . '/pens_request_receipt.php';
require_once __DIR__ . '/pens_request_collect.php';
require_once __DIR__ . '/pens_request_alert.php';

/**
 * PENSRequestFactory
 * 
 * Class used to create a PENSRequestCollect, PENSRequestReceipt or PENSRequestAlert object, depending on the "command" argument
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class PENSRequestFactory {

	/**
	 * Factory method
	 * 
	 * Creates the right PENSRequest object, based on the command inside the arguments
	 * 
	 * @param array Associative array of arguments
	 * @return mixed PENSRequestAlert or PENSRequestCollect or PENSRequestReceipt
	 * 
	 * @throws PENSException with code 2002 if command is invalid
	 */
	public static function createPENSRequest($arguments) {
		$command = $arguments["command"];
		if($command == "alert") {
			return new PENSRequestAlert($arguments);
		} else if($command == "collect") {
			return new PENSRequestCollect($arguments);
		} else if($command == "receipt") {
			return new PENSRequestReceipt($arguments);
		} else {
			throw new PENSException(2002);
		}
	}
}
