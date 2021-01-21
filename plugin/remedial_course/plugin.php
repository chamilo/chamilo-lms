<?php
require_once __DIR__.'/config.php';

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
$plugin_info = RemedialCoursePlugin::create()->get_info();
$plugin_info['title'] = 'Remedial and Advance Courses';
$plugin_info['comment'] = 'It adds the possibility of enrolling the user in a remedial course when the last '.
    'attempt of an exercise fails or an advanced course when they pass an exercise. The success rate of '.
    'the exercise must be established';
$plugin_info['version'] = '1.0'; // o la versión que corresponda
$plugin_info['author'] = 'Carlos Alvarado';

$strings['plugin_title'] = 'Remedial and Advance Courses';
$strings['title'] = 'Remedial and Advance Courses';
$strings['enabled'] = 'Enabled';
$strings['plugin_comment'] = 'It adds the possibility of enrolling the user in a remedial course when the last '.
    'attempt of an exercise fails or an advanced course when they pass an exercise. The success rate of '.
    'the exercise must be established';
