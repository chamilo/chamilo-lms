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
 * PENSController
 * 
 * Provides the PENSController class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */

require_once __DIR__.'/pens_request_factory.php';

/**
 * PENSController
 * 
 * Base class for the PENSServer and PENSClient
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
abstract class PENSController {
	
	/**
	 * Parses a request based on the values present in $_REQUEST
	 * 
	 * @return PENSRequest Request generated
	 * @throws PENSException if the request could not be parsed
	 */
	protected function parseRequest() {
		$request = PENSRequestFactory::createPENSRequest($_REQUEST);
		return $request;
	}
	
	/**
	 * Sends the HTTP Response to the client
	 * 
	 * @param PENSResponse Response to be sent
	 */
	protected function sendResponse($response) {
		$response->send();
	}
	
}
