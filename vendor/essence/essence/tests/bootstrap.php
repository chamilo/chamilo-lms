<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

require_once dirname( dirname( __FILE__ ))
	. DIRECTORY_SEPARATOR . 'lib'
	. DIRECTORY_SEPARATOR . 'bootstrap.php';



/**
 *	Definitions
 */

if ( !defined( 'ESSENCE_TEST' )) {
	define( 'ESSENCE_TEST', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
}

if ( !defined( 'ESSENCE_HTTP' )) {
	define( 'ESSENCE_HTTP', ESSENCE_TEST . 'http' . DIRECTORY_SEPARATOR );
}
