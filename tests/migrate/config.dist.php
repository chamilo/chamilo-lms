<?php

/**
 * This is the configuration file allowing you to connect to the origin
 * database. You should either fill this one in or generate your own
 * copy as config.php
 */
/**
 * Define all connection variables
 */
/*
 * The database type allows you to define with database driver to use.
 * Currently allowed values are: mssql. Defaults to: mssql
 */
$db_type = 'mssql';
/*
 * The database host is the name of the server on which the origin
 * database is located. This name should be routeable by PHP.
 * Defaults to: localhost
 */
//$db_host = 'localhost';
/*
 * The database port is the port on which to connect on the origin
 * database host. The default port for MS-SQL is 1433, which we
 * use as a default port here. Defaults to: 1433
 */
$db_port = '1433';
/*
 * The database user is the name under which to connect to the 
 * origin database server. Defaults to: lms
 */
$db_user = 'user_of_db';
/*
 * The database password is the password for the user on the origin
 * database server. Defaults to: password
 */
$db_pass = '**********';
/*
 * The database name on the database origin server.
 * Defaults to: master
 */
$db_name = 'master1';
//second DB 
$db_name2 = 'master';


$config = array(    
    'type' => $db_type,
    'host' => $db_host,
    'port' => $db_port,
    'db_user' => $db_user,
    'db_pass' => $db_pass,
    'db_name' => $db_name,    
);
$config2 = array(    
    'type' => $db_type,
    'host' => $db_host,
    'port' => $db_port,
    'db_user' => $db_user,
    'db_pass' => $db_pass,
    'db_name' => $db_name2,    
);

$servers = array(
    array(  'name'          => 'Old ms',
            'filename'      => 'db_matches.php',   
            'connection'    => $config,
            'active'        => false
    ),
    array(  'name' => 'with e class stuff',
            'filename'      => 'db_matches_2.php',   
            'connection'    => $config2,
            'active'        => true
    ),    
);


/**
 * Load the real configuration file (except if we're already in config.php)
 */
if ((basename(__FILE__) != 'config.php') && is_file('config.php')) {
    include 'config.php';
}
