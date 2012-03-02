<?php
/* See license terms in /license.txt */
class AppPlugin {
    var $plugin_list = array ( 
 //           'loginpage_main',
            'login',
            'menu', 
/*            'campushomepage_main', 
            'campushomepage_menu',
            'mycourses_main', 
            'mycourses_menu',*/
            'content_top',
            'content_bottom',
            'header_main',
            'header_center',
            'header_left',
            'header_right',            
            //'footer',
            'footer_left',
            'footer_center',
            'footer_right',
            'course_tool_plugin'
    );
    
    function __construct() {
        
    }
    
    /*  For each of the possible plugin directories we check whether a file named "plugin.php" exists
        (it contains all the needed information about this plugin).
        This "plugin.php" file looks like:
        $plugin_info['title'] = 'The title of the plugin';
        $plugin_info['comment'] = 'Some comment about the plugin';
        $plugin_info['location'] = array('loginpage_menu', 'campushomepage_menu', 'banner'); // The possible locations where the plugins can be used.
        $plugin_info['version'] = '0.1 alpha'; // The version number of the plugin.
        $plugin_info['author'] = 'Patrick Cool'; // The author of the plugin.
    */
    function read_plugins_from_path() {
        /* We scan the plugin directory. Each folder is a potential plugin. */
        $pluginpath = api_get_path(SYS_PLUGIN_PATH);
        $possible_plugins = array();
        $handle = @opendir($pluginpath);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && is_dir(api_get_path(SYS_PLUGIN_PATH).$file)) {
                $possible_plugins[] = $file;
            }
        }
        @closedir($handle);
        sort($possible_plugins);   
        return $possible_plugins;
    }
    
    function get_installed_plugins() {
        $usedplugins = array();
        /* We retrieve all the active plugins. */    
        $result = api_get_settings('Plugins');    
        foreach ($result as $row) {
            $usedplugins[$row['variable']][] = $row['selected_value'];
        }
        return $usedplugins;
    }
    
    function get_areas_by_plugin($plugin_name) {
        $result = api_get_settings('Plugins');        
        $areas = array();
        foreach ($result as $row) {
            if ($plugin_name == $row['selected_value']) {
                $areas[] = $row['variable'];
            }
        }
        return $areas;
    }
    
    function is_valid_plugin_location($location) {
        return in_array($location, $this->plugin_list);
    }
    
    function is_valid_plugin($plugin_name) {
        if (is_dir(api_get_path(SYS_PLUGIN_PATH).$plugin_name)) {
            if (is_file(api_get_path(SYS_PLUGIN_PATH).$plugin_name.'/index.php')) {
                return true;
            }
        }
        return false;
    }
    
    function get_plugin_list() {
        sort($this->plugin_list);
        return $this->plugin_list;
    }
}