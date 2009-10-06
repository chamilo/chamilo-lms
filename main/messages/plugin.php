<?php
/**
 * This script is a configuration file for the messages plugin. You can use it as a master for other plugins.
 * These settings will be used in the administration interface for plugins (Dokeos configuration settings->Plugins)
 * @package dokeos.plugin
 * @author Evie, Free University of Brussels
 */
/**
 * Plugin details (must be present)
 */
//the plugin title
$plugin_info['title']='Messages';
//the comments that go with the plugin
$plugin_info['comment']="Private messages plugin";
//the locations where this plugin can be shown
$plugin_info['location']=array('loginpage_main', 'loginpage_menu', 'campushomepage_main', 'campushomepage_menu', 'mycourses_main', 'mycourses_menu', 'header', 'footer');
//the plugin version
$plugin_info['version']='1.1';
//the plugin author
$plugin_info['author']='Facultad de Matematicas, UADY (Mexico)';
?>
