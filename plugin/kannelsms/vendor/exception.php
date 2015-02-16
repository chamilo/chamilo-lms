<?php
/**
 * Kannel PHP API
 *
 * @package     Kannel
 * @copyright   Mediaburst Ltd 2012
 * @license     ISC
 * @link        http://www.kannelsms.com
 */

/*
 * KannelException
 *
 * The Kannel wrapper class will throw these if a general error
 * occurs with your request, for example, an invalid API key.
 *
 * @package     Kannel
 * @subpackage  Exception
 * @since       1.0
 */
class KannelException extends \Exception {

    public function __construct( $message, $code = 0 ) {
        // make sure everything is assigned properly
        parent::__construct( $message, $code );
    }
}
