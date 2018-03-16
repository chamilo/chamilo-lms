<?php
/* For license terms, see /license.txt */
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins).
 *
 * @package chamilo.plugin.buycourses
 */
/**
 * Plugin details (must be present).
 */
require_once __DIR__.'/config.php';
$plugin_info = BuyCoursesPlugin::create()->get_info();
