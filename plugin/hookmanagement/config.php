<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 * @package chamilo.plugin.hookmanagement
 */

define('TABLE_PLUGIN_HOOK_OBSERVER', 'plugin_hook_observer');
define('TABLE_PLUGIN_HOOK_CALL', 'plugin_hook_call');
define('TABLE_PLUGIN_HOOK_EVENT', 'plugin_hook_event');
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(PLUGIN_PATH) . 'hookmanagement/src/HookManagementPlugin.class.php';
