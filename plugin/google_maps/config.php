<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 * @package chamilo.plugin.google_maps
 */
//require_once '../../main/inc/global.inc.php';

define('TABLE_GOOGLE_MAPS', 'plugin_google_maps');

require_once api_get_path(SYS_PATH) . 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH) . 'google_maps/src/google_maps_plugin.class.php';
// Edit the config/tour.json file to add more pages or more elements to the guide
