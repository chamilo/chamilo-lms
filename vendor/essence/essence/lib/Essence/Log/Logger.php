<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Log;



/**
 *	A very basic logger.
 *	Inspired by PSR log (https://github.com/php-fig/log).
 *
 *	@package Essence.Log
 */

interface Logger {

	/**
	 *	Log level for normal but significant events.
	 *
	 *	@var string
	 */

	const notice = 'notice';



	/**
	 *	Logs a message.
	 *
	 *	@param mixed $level Level.
	 *	@param string $message Message.
	 *	@param array $context Context.
	 */

	public function log( $level, $message, array $context = [ ]);

}
