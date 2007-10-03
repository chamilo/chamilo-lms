<?php //$id: $
/**
 * This script is a configuration file for the search plugin. 
 * You can use it as a master for other plugins.
 * These settings will be used in the administration interface for 
 * plugins (Dokeos configuration settings->Plugins)
 * Make sure your read the README.txt file to understand how to use this plugin!
 * @package dokeos.plugin 
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
/**
 * Plugin details (must be present) 
 */
//the plugin title
$plugin_info['title']='Search';
//the comments that go with the plugin
$plugin_info['comment']="Full-text search engine";
//the locations where this plugin can be shown
$plugin_info['location']=array('mycourses_main', 'mycourses_menu', 'header', 'footer');
//the plugin version
$plugin_info['version']='1.0';
//the plugin author
$plugin_info['author']='Yannick Warnier';
?>
