<?php
/**
 * Clockwork PHP API
 *
 * @package     Clockwork
 * @copyright   Mediaburst Ltd 2012
 * @license     ISC
 * @link        http://www.clockworksms.com
 */ 

/*
 * ClockworkException
 *
 * The Clockwork wrapper class will throw these if a general error
 * occurs with your request, for example, an invalid API key.
 *
 * @package     Clockwork
 * @subpackage  Exception
 * @since       1.0
 */
class ClockworkException extends Exception {

    public function __construct( $message, $code = 0 ) {
        // make sure everything is assigned properly
        parent::__construct( $message, $code );
    }
}
