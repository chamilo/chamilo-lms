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
$plugin_info['title'] = 'Reports: "Distribution by entity" and "Distribution by author"';
$plugin_info['comment'] = 'To enable report, You must activate the user subscription to the learning path through the '.
    '"Course settings" -> "Subscribe users to learning path" <br>'.
    'Then you can go to /main/mySpace/ to see the reports.';
$plugin_info['version'] = '1.1'; // o la versión que corresponda
$plugin_info['author'] = 'Carlos Alvarado';
