<?php
exit;
/**
 * This script is to be used from PHP command line and will create a set
 * of Virtual VChamilo automatically from a CSV nodelist description.
 * The standard structure of the nodelist is given by the nodelist-dest.csv file.
 */

global $debuglevel;
global $debugdisplay;
$debuglevel = 4;
$debugdisplay = 4;

define('CLI_SCRIPT', true);
define('CHAMILO_INTERNAL', true);

// this will only run on master chamilo
echo "Starting tool\n";
echo "Chamilo Bulk Nodes Creation v.1.0\n";
echo "=================================\n";
require_once('../../../main/inc/global.inc.php');
require_once('clilib.php'); // cli only functions
// Ensure errors are well explained
ini_set('debug_display', 1);
ini_set('debug_level', E_ALL);

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'interactive' => false,
        'help'        => false,
        'config'      => false,
        'nodes'       => '',
        'lint'        => false
    ),
    array(
        'h' => 'help',
        'c' => 'config',
        'n' => 'nodes',
        'i' => 'interactive',
        'l' => 'lint'
    )
);

$interactive = !empty($options['interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Command line VMoodle Generator.
Please note you must execute this script with the same uid as apache!

Options:
--interactive     No interactive questions or confirmations
-h, --help            Print out this help
-c, --config          Define an external config file
-n, --nodes           A node descriptor CSV file
-l, --lint            Decodes node file and give a report on nodes to be created.

Example:
\$sudo -u www-data /usr/bin/php /var/www/chamilo19/plugin/vchamilo/cli/bulkdestroynodes.php --nodes=<node-file-path>
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

// Get all options from config file.
if (!empty($options['config'])) {
    echo "Loading config : ".$options['config'];
    if (!file_exists($options['config'])) {
        cli_error(get_string('confignotfound', 'block_vmoodle'));
    }
    $content = file($options['config']);
    foreach ($content as $l) {
        if (preg_match('/^\s+$/', $l)) {
            continue; // Empty lines.
        }
        if (preg_match('/^[#\/!;]/', $l)) {
            continue; // Comments (any form).
        }
        if (preg_match('/^(.*?)=(.*)$/', $l, $matches)) {
            if (in_array($matches[1], $expectedoptions)) {
                $options[trim($matches[1])] = trim($matches[2]);
            }
        }
    }
}

require_once($_configuration['root_sys'].'local/classes/database.class.php'); // cli only functions
if ($options['verbose']) {
    echo "loaded dbclass\n";
}
require_once($_configuration['root_sys'].'local/classes/textlib.class.php'); // cli only functions
if ($options['verbose']) {
    echo "loaded textlib\n";
}
require_once($_configuration['root_sys'].'local/classes/mootochamlib.php'); // moodle like API
if ($options['verbose']) {
    echo "loaded moodle wrapping\n";
}
require_once($_configuration['root_sys'].'/plugin/vchamilo/lib/vchamilo_plugin.class.php');
if ($options['verbose']) {
    echo "loaded vchamilo plugin\n";
}

global $DB;
if ($options['verbose']) {
    echo "building database manager\n";
}
$DB = new DatabaseManager();
if ($options['verbose']) {
    echo "building plugin vchamilo\n";
}
$plugin = VChamiloPlugin::create();

if (empty($options['nodes'])) {
    cli_error(get_string('climissingnodes', 'block_vmoodle'));
}

if ($options['verbose']) {
    echo "parsing nodelist\n";
}
$nodes = vchamilo_parse_csv_nodelist($options['nodes'], $plugin);

if ($options['lint']) {
    ctrace("Lint mode:\n");
    print_object($nodes);
    die;
}

if (empty($nodes)) {
    cli_error(get_string('cliemptynodelist', 'block_vmoodle'));
}

ctrace('Starting CLI processing');

foreach ($nodes as $n) {

    ctrace('Destroying node :'.$n->vhostname);

    if (!$DB->get_record('vchamilo', array('root_web' => $n->root_web))) {
        ctrace('Node does not exist. Skipping');
        continue;
    }

    /*
     * This launches automatically all steps of the controller.management.php script several times
     * with the "doadd" action and progressing in steps.
     */
    $action = "fulldeleteinstances";

    $automation = true;

    $return = include($_configuration['root_sys'].'/plugin/vchamilo/views/manage.controller.php');
    if ($interactive) {
        $input = readline("Continue (y/n|r) ?\n");
        if ($input == 'r' || $input == 'R') {
            $vmoodlestep--;
        } elseif ($input == 'n' || $input == 'N') {
            echo "finishing\n";
            exit(0);
        }
    }
}
exit (0);
