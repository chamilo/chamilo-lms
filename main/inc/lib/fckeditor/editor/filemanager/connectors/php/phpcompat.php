<?php

if ( !isset( $_SERVER ) ) {
    $_SERVER = $HTTP_SERVER_VARS ;
}
if ( !isset( $_GET ) ) {
    $_GET = $HTTP_GET_VARS ;
}
if ( !isset( $_FILES ) ) {
    $_FILES = $HTTP_POST_FILES ;
}

if ( !defined( 'DIRECTORY_SEPARATOR' ) ) {
    define( 'DIRECTORY_SEPARATOR',
        IS_WINDOWS_OS ? '\\' : '/'
    ) ;
}
