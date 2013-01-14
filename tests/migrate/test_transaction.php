<?php
/**
 * This script will test the recovery of transactions from an external database
 * queried through web services.
 */
ini_set('display_errors',1);
ini_set('mssql.datetimeconvert',0);
$eol = "<br />";
if (PHP_SAPI == 'cli') {
  $eol = "\n";
}
/**
 * Load connect info
 */
require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
require 'config.php';
if (is_file(dirname(__FILE__) . '/migration.custom.class.php')) {
    require_once 'migration.custom.class.php';
} else {
    die("You need to define a custom migration class as migration.custom.class.php\n(copy it from migration.custom.class.dist.php, otherwise this migration process\n will not know what to do with the original database fields\n");
}
require_once 'migration.class.php';
if (empty($servers)) {
    die("This script requires a servers array with the connection settings and the file to parse\n");
}
$start = time();
$branches = array();
/**
 * Try connecting
 */
if (!empty($servers)) {
    foreach($servers as $server_info) {
        if ($server_info['active']) {
            $branch = $server_info['branch_id'];
            $config_info = $server_info['connection'];
            $db_type = $config_info['type'];
            if (empty($db_type)) {
                die("This script requires a DB type to work. Please update config.php\n");
            }
            $file = dirname(__FILE__) . '/migration.' . $db_type . '.class.php';
            if (!is_file($file)) {
                die("Could not find db type file " . $file . "\n");
            }
            require_once $file;
            $matches = null;
            include $server_info['filename'];
            if (!isset($matches['web_service_calls']['filename'])) {
                continue;
            }
            $branches[] = $branch;
            //echo 'Found server '.$server_info['name'].$eol.$eol;
            require_once $matches['web_service_calls']['filename'];
            $class = 'Migration' . strtoupper($db_type);
            $m = new $class($config_info['host'], $config_info['port'], $config_info['db_user'], $config_info['db_pass'], $config_info['db_name'], $boost);
            $m->connect();
            $m->search_transactions($matches['web_service_calls']);
            //Load transactions saved before
            if (!empty($_POST['ok'])) {
                $m->load_transactions($matches);
            }
            
        }
    }
}
/**
 * Show form
 */
echo '<p><form action="" method="POST">';
echo '<table>';
echo '<tr><!--td>Sede</td><td><select name="branch">';
foreach ($branches as $branch) {
    echo '<option value="'.$branch.'">'.$branch.'</option>';
}
echo '</select></td-->';
echo '<td><input type="submit" name="ok" value="Probar una transaccion"></td></tr>';
echo '</table>';
echo '</form></p>';
echo "$eol";