<?php
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
error_log('[chamilo][cronjob] Starting cron jobs as process '.getmypid());
require_once 'notification.php';
error_log('[chamilo][cronjob] Ending cron jobs of process '.getmypid());
