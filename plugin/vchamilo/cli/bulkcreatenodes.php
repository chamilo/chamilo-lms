<?php
exit;
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

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array(
        'interactive'       => false,
        'help'              => false,
        'config'            => false,
        'nodes'             => '',
        'lint'              => false,
        'verbose'           => false
    ),
    array(
        'h' => 'help',
        'i' => 'interactive',
        'c' => 'config',
        'n' => 'nodes',
        'l' => 'lint',
        'v' => 'verbose'
    )
);

$interactive = !empty($options['interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Cli unkown options\n".$unrecognized);
}

if ($options['help']) {
    $help = "Command line VChamilo Generator.
Please note you must execute this script with the same uid as apache!

Options:
--interactive         Blocks on each step and waits for input to continue
-h, --help            Print out this help
-c, --config          Define an external config file
-n, --nodes           A node descriptor CSV file
-l, --lint            Decodes node file and give a report on nodes to be created.

Example:
\$sudo -u www-data /usr/bin/php /var/www/chamilo/plugin/vchamilo/cli/bulkcreatenodes.php --nodes=<nodelist>
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

// Get all options from config file.

if (!empty($options['config'])) {
    echo "Loading config : ".$options['config'];
    if (!file_exists($options['config'])) {
        cli_error('Config file mentioned but not found');
    }

    $content = file($options['config']);
    foreach ($content as $l) {
        if (preg_match('/^\s+$/', $l)) continue; // Empty lines.
        if (preg_match('/^[#\/!;]/', $l)) continue; // Comments (any form).
        if (preg_match('/^(.*?)=(.*)$/', $l, $matches)) {
            if (in_array($matches[1], $expectedoptions)) {
                $options[trim($matches[1])] = trim($matches[2]);
            }
        }
    }
}

require_once($_configuration['root_sys'].'local/classes/database.class.php'); // cli only functions
require_once($_configuration['root_sys'].'local/classes/textlib.class.php'); // cli only functions
require_once($_configuration['root_sys'].'local/classes/mootochamlib.php'); // moodle like API
require_once($_configuration['root_sys'].'/plugin/vchamilo/lib/vchamilo_plugin.class.php');

global $DB;
if ($options['verbose']) echo "building database manager\n";
$DB = new DatabaseManager();
if ($options['verbose']) echo "building plugin vchamilo\n";
$plugin = VChamiloPlugin::create();

if (empty($options['nodes'])) {
    cli_error('Missing node definition. Halt.');
}

if ($options['verbose']) echo "parsing nodelist\n";
$nodes = vchamilo_parse_csv_nodelist($options['nodes'], $plugin);

if ($options['lint']) {
    ctrace("Lint mode:\n");
    print_object($nodes);
    die;
}

if (empty($nodes)) {
    cli_error('Node list empty');
}

ctrace('Starting generation');

// Get main admin for further replacement.
$admin = $DB->get_record('user', array('username' => 'admin'));

foreach ($nodes as $data) {

    ctrace('Making node '.$data->root_web);

    if (!empty($data->template)) {
        ctrace('Using template '.$data->template);

        if (!vchamilo_template_exists($data->template)) {
            ctrace('Template not found. Skipping node.');
            continue;
        }
    }

    if ($DB->get_record('vchamilo', array('root_web' => $data->root_web))) {
        ctrace('Node exists. skipping');
        continue;
    }

    $data->what = 'addinstance';
    $data->registeronly = false;

    $NDB = null;

    $automation = true;
    $return = include($_configuration['root_sys'].'plugin/vchamilo/views/editinstance.controller.php');
    if ($return == -1) {
        cli_error('Node create process error');
    }

    // This is specific code for presetting any plugin data per instance from the CSV
    ctrace('Setting up ent_installer');
    if ($NDB) {
        // Copy admin account info from master
        $NDB->set_field('user', 'password', $admin->password, array('username' => 'admin'), 'user_id');

        // Setting ENT_installer values
        if (!empty($data->ent_installer)) {
            foreach ($data->ent_installer as $setting => $value) {
                $settingrec = new StdClass();
                $settingrec->variable = 'ent_installer_'.$setting;
                $settingrec->subkey = 'ent_installer';
                $settingrec->type = 'setting';
                $settingrec->category = 'Plugins';
                $settingrec->access_url = 1;
                $settingrec->selected_value = $value;
                ctrace("Setting up {$settingrec->variable}|{$settingrec->subkey} to $value\n");
                if ($oldrec = $NDB->get_record('settings_current', array('variable' => $settingrec->variable, 'subkey' => $settingrec->subkey, 'type' => $settingrec->type))) {
                    $settingrec->id = $oldrec->id;
                    $NDB->update_record('settings_current', $settingrec, 'id');
                } else {
                    $NDB->insert_record('settings_current', $settingrec);
                }
            }
        }

        // updating other config values
        if (!empty($data->config)) {
            ctrace("VChamilo has config overrides\n");
            foreach ($data->config as $configkey => $configsetting) {
                ctrace("Setting up {$configkey}");
                // Note you can just alter existing settings here as we cannot pull enough data from csv headers to get a complete setting descriptor.
                $settingrec = new StdClass();
                $settingrec->variable = $configkey;
                if (!empty($settingrec->subkey)) {
                    $settingrec->subkey = $configsetting->subkey;
                }
                // $settingrec->type = 'setting';
                // $settingrec->category = 'Plugins';
                // $settingrec->access_url = 1;
                $settingrec->selected_value = $configsetting->value;

                if (!empty($settingrec->subkey)) {
                    $params = array('variable' => $settingrec->variable, 'subkey' => $settingrec->subkey);
                } else {
                    $params = array('variable' => $settingrec->variable);
                }

                if ($oldrec = $NDB->get_record('settings_current', $params)) {
                    ctrace("Updating {$settingrec->variable}|{$settingrec->subkey} to $configsetting->value\n");
                    $settingrec->id = $oldrec->id;
                    $NDB->update_record('settings_current', $settingrec, 'id');
                }
            }
        }
        $NDB->dismount();
    } else {
        ctrace('No Side CNX for setup');
    }

    if ($interactive) {
        $input = readline("Continue (y/n|r) ?\n");
        if ($input == 'r' || $input == 'R') {
            // do nothing, just continue
        } elseif ($input == 'n' || $input == 'N') {
            echo "finishing\n";
            exit;
        }
    }
}
