<?php
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other platform plugins (course plugins are slightly different).
 * These settings will be used in the administration interface for plugins (Chamilo configuration settings->Plugins)
 * @package chamilo.plugin
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Plugin details (must be present)
 */

//the plugin title
$plugin_info['title']       = 'Show user information';
//the comments that go with the plugin
$plugin_info['comment']     = "Shows the user information";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Julio Montoya';

//set the smarty templates that are going to be used
$plugin_info['templates']   = array('template.tpl');