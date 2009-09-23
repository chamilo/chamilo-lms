<?php //$id: $
/**
 * This script is a configuration file for the date plugin. You can use it as a master for other plugins.
 * These settings will be used in the administration interface for plugins (Dokeos configuration settings->Plugins)
 * @package dokeos.plugin
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Plugin details (must be present)
 */
//the plugin title
$plugin_info['title']='Date';
//the comments that go with the plugin
$plugin_info['comment']="Multinational date display";
//the locations where this plugin can be shown
$plugin_info['location']=array('loginpage_main', 'loginpage_menu', 'campushomepage_main', 'campushomepage_menu', 'mycourses_main', 'mycourses_menu', 'header', 'footer');
//the plugin version
$plugin_info['version']='1.0';
//the plugin author
$plugin_info['author']='Yannick Warnier';
?>
