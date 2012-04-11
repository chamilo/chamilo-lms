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

/* Plugin config */

//the plugin title
$plugin_info['title']       = 'Follow icons';
//the comments that go with the plugin
$plugin_info['comment']     = "Add social icons (implemented using addthis.com)";
//the plugin version
$plugin_info['version']     = '1.0';
//the plugin author
$plugin_info['author']      = 'Julio Montoya';

$plugin_info['templates']   = array('template.tpl');

//For bigger icons change this value to addthis_32x32_style
$plugin_info['icon_class']   = ''; 

//To use vertical alignment change this value to 
$plugin_info['position']   = 'addthis_default_style'; //addthis_vertical_style
