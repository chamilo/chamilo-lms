#!/usr/bin/php -q
<?php

/**
 *	@author Félix Girault <felix.girault@gmail.com>
 *	@license FreeBSD License (http://opensource.org/licenses/BSD-2-Clause)
 */

require_once dirname( dirname( __FILE__ )) . '/lib/bootstrap.php';



/**
 *
 */

if ( $argc < 3 ) {
	echo "Too few arguments.\n";
} else {
	main( $argv[ 1 ], $argv[ 2 ]);
}



/**
 *
 */

function main( $method, $url ) {

	$Essence = Essence\Essence::instance( );

	switch ( $method ) {
		case 'embed':
			dumpMedia( $Essence->embed( $url ));
			break;

		case 'extract':
			dumpArray( $Essence->extract( $url ));
			break;
	}
}



/**
 *
 */

function dumpMedia( $Media ) {

	if ( !$Media ) {
		echo "No results.\n";
		return;
	}

	$data = [ ];

	foreach ( $Media as $key => $value ) {
		if ( $value ) {
			$data[ $key ] = $value;
		}
	}

	dumpArray( $data );
}



/**
 *
 */

function dumpArray( array $data ) {

	if ( empty( $data )) {
		echo "No results.\n";
		return;
	}

	$lengths = array_map( 'strlen', array_keys( $data ));
	$length = max( $lengths );

	foreach ( $data as $key => $value ) {
		printf( "%{$length}s: %s\n", $key, $value );
	}
}
