<?php
/**
 * Base process migration script. This script reads a specific configuration file and contacts libraries
 * in order to know what to migrate and where
 * @package chamilo.migration
 */
/**
 * Load required classes
 */
require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
require_once 'config.php';

require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'thematic.lib.php';

error_reporting(-1);

if (is_file(dirname(__FILE__) . '/migration.custom.class.php')) {
    require_once 'migration.custom.class.php';
} else {
    die("You need to define a custom migration class as migration.custom.class.php\n(copy it from migration.custom.class.dist.php, otherwise this migration process\n will not know what to do with the original database fields\n");
}
require_once 'migration.class.php';

if (empty($servers)) {
    die("This script requires a servers array with the connection settings and the file to parse\n");
}
/**
 * Read the configuration file
 */
if (!empty($servers)) {
    foreach ($servers as $server_info) {
        if ($server_info['active']) {
            echo "\n---- Start loading server----- \n";
            echo $server_info['name']."\n\n";
            
            $config_info = $server_info['connection'];
            $db_type = $config_info['type'];
            
            if (empty($db_type)) {
                die("This script requires a DB type to work. Please update orig_db_conn.inc.php\n");
            }
            $file = dirname(__FILE__) . '/migration.' . $db_type . '.class.php';
            if (!is_file($file)) {
                die("Could not find db type file " . $file . "\n");
            }
            require_once $file;
            $class = 'Migration' . strtoupper($db_type);
            $m = new $class($config_info['host'], $config_info['port'], $config_info['db_user'], $config_info['db_pass'], $config_info['db_name']);
            $m->connect();
            if ($server_info['m']) {
                /**
                 * Prepare the arrays of matches that will allow for the migration
                 */
                $migrate = array();
                include $server_info['filename'];
                //Default migration from MSSQL to Chamilo MySQL
                $m->migrate($matches);
            } elseif ($server_info['s']) {
                //Getting transactions from MSSQL (via webservices)
                if (isset($matches['web_service_calls']['filename'])) {
                    require_once $matches['web_service_calls']['filename'];
                }
                //This functions truncates the transaction lists!
                $m->test_transactions($matches['web_service_calls']);
                //$m->search_transactions($matches['web_service_calls']);
            
                //Load transactions saved before            
                $m->load_transactions($matches);
            }
            echo "\n ---- End loading server----- \n";
        } else {
            error_log("db_matches not activated: {$server_info['name']} {$server_info['filename']}");
        }
    }
}
