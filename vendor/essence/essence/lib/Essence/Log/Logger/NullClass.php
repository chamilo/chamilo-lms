<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

namespace Essence\Log\Logger;

use Essence\Log\Logger;



/**
 *	Does absolutely nothing.
 *
 *	@package Essence.Log.Logger
 */

class NullClass implements Logger {

	/**
	 *	{@inheritDoc}
	 */

	public function log( $level, $message, array $context = [ ]) { }

}
