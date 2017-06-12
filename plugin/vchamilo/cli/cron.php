<?php
/* For license terms, see /license.txt */
exit;
/**
 * This script should be called by a properly set cron process on your server.
 * For more information, check the installation guide in the documentation
 * folder.
 * Add your own executable scripts below the inclusion of notification.php
 * @package chamilo.cron
 */
/**
 * Settings that will influence the execution of the cron tasks
 */
//ini_set('max_execution_time',300); //authorize execution for up to 5 minutes
//ini_set('memory_limit','100M'); //authorize script to use up to 100M RAM
/**
 * Included cron-ed tasks. You might want to turn error-logging off by
 * commenting the first and last line of this section.
 */

define('CLI_SCRIPT', true); // for chamilo imported code
define('CHAMILO_INTERNAL', true);
global $CLI_VCHAMILO_PRECHECK;

$CLI_VCHAMILO_PRECHECK = true; // force first config to be minimal
require(dirname(dirname(dirname(__DIR__))).'/main/inc/conf/configuration.php'); // get boot config
require_once($_configuration['root_sys'].'plugin/vchamilo/cli/clilib.php'); // cli only functions

// Ensure errors are well explained

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'host'              => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Command line chamilo CRON

Options:
-h, --help          Print out this help
-H, --host          Set the host (physical or virtual) to operate on

"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // arms the vchamilo switching
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VCHAMILO_OVERRIDE', $options['host']);
}
// replay full config whenever. If vchamilo switch is armed, will switch now config
require($_configuration['root_sys'].'main/inc/conf/configuration.php'); // do REALLY force configuration to play again, or the following call will not have config twicked (require_once)
echo('Config check : playing for '.$_configuration['root_web']."\n");

error_log('[chamilo][cronjob] Starting cron jobs as process '.getmypid());
echo '<pre>';
echo ('[chamilo][cronjob] Starting cron jobs as process '.getmypid()."\n");
require_once $_configuration['root_sys'].'main/cron/notification.php';
error_log('[chamilo][cronjob] Ending cron jobs of process '.getmypid());
echo('[chamilo][cronjob] Ending cron jobs of process '.getmypid()."\n");
echo '</pre>';
