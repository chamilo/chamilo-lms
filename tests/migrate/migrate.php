<?php
/**
 * Load required classes
 */
require_once '../../main/inc/global.inc.php';
require_once 'config.dist.php';
if (is_file(dirname(__FILE__).'/migration.custom.class.php')) {
  require_once 'migration.custom.class.php';
} else {
  die ("You need to define a custom migration class as migration.custom.class.php\n(copy it from migration.custom.class.dist.php, otherwise this migration process\n will not know what to do with the original database fields\n");
}
require_once 'migration.class.php';

if (empty($db_type)) {
  die("This script requires a DB type to work. Please update orig_db_conn.inc.php\n");
}
$file = dirname(__FILE__).'/migration.'.$db_type.'.class.php';
if (!is_file($file)) {
  die("Could not find db type file ".$file."\n");
}
require_once $file;
$class = 'Migration'.strtoupper($db_type);
$m = new $class($db_host,$db_port,$db_user,$db_pass,$db_name);
$m->connect();
/**
 * Prepare the arrays of matches that will allow for the migration
 */
$migrate = array();
include 'db_matches.php';
$m->migrate($matches);
print_r($m->errors_stack);
echo "OK so far\n";
