<?php

/* For licensing terms, see /license.txt */

/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins
 * (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins).
 *
 * @package chamilo.plugin
 *
 * @author Carlos Alvarado <alvaradocarlo@gmail.com>
 */
/**
 * Plugin details (must be present).
 */
$plugin_info['title'] = 'Mailing new LPs to students and their HR Managers';
$plugin_info['comment'] = 'Add the ability to send emails to students and hr when a lp is published. It will be sent in each execution of the respective cron "main/cron/learning_path_reminder.php"';
$plugin_info['version'] = '1.0';
$plugin_info['author'] = 'Carlos Alvarado';
