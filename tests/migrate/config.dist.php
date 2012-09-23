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
$db_host = 'localhost';
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
$db_user = 'lms';
/*
 * The database password is the password for the user on the origin
 * database server. Defaults to: password
 */
$db_pass = 'password';
/*
 * The database name on the database origin server.
 * Defaults to: master
 */
$db_name = 'master';

/**
 * Load the real configuration file (except if we're already in config.php)
 */
if ((basename(__FILE__) != 'config.php') && is_file('config.php')) {
  include 'config.php'; 
}
