<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
//require_once '../../main/inc/global.inc.php';

define('TABLE_TOUR_LOG', 'plugin_tour_log');

require_once api_get_path(SYS_PATH) . 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(SYS_PLUGIN_PATH) . 'tour/src/tour_plugin.class.php';
// Edit the config/tour.json file to add more pages or more elements to the guide
