<?php
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Plugin details (must be present)
 */

require_once dirname(__FILE__) . '/config.php';
$plugin_info = Buy_CoursesPlugin::create()->get_info();
