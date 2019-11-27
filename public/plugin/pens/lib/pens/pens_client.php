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
 * PENSClient
 * 
 * Provides the PENSClient class
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
 
require_once __DIR__.'/pens_controller.php';
require_once __DIR__.'/pens_request_handler.php';

/**
 * PENSClient
 * 
 * Class that implements the PENS Client
 * 
 * @package PENS
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class PENSClient extends PENSController {
	/**
	 * Instance of the PENSClient
	 * @var PENSServer
	 */
	private static $_instance;
	
	/**
	 * Request handler
	 * @var PENSRequestHandler
	 */
	protected $_request_handler = null;
	
	/**
	 * Private constructor
	 */
	private function __construct() {
	}
	
	/**
	 * Singleton method
	 */
	public static function singleton() {
		if(!isset(self::$_instance)) {
			$c = __CLASS__;
			self::$_instance = new $c;
		}
		return self::$_instance;
	}
	
	/**
	 * Prevent users to clone the instance
	 */
	public function __clone() {
		trigger_error('Clone is not allowed', E_USER_ERROR);
	}
	
	public function getRequestHandler() {
		return $this->_request_handler;
	}
	
	/**
	 * Sets the request handler. Does nothing if the argument is not an instance of PENSRequestHandler
	 * 
	 * @param PENSRequestHandler Request handler
	 */
	public function setRequestHandler($request_handler) {
		if($request_handler instanceof PENSRequestHandler) {
			$this->_request_handler = $request_handler;
		}
	}
	
	/**
	 * Receives a request, calls the handler and displays the response
	 */
	public function receiveRequest() {
		$request = null;
		try {
			$request = $this->parseRequest();
			$command = $request->getCommand();
			if($command == "alert" || $command == "receipt") {
				if(!is_null($this->_request_handler)) {
					// Call the handler
					$this->_request_handler->processRequest($request, new PENSResponse($_REQUEST));
				}
				$this->sendResponse(new PENSResponse(0, $command." received and understood"));
			}
		} catch(PENSException $e) {
			// If we could not parse the request, send the error
			$this->sendResponse(new PENSResponse($e));
		}
	}
}
