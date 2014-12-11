<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 * @package chamilo.plugin.advancedsubscription
 */

define('TABLE_ADV_SUB_QUEUE', 'plugin_advsub_queue');
define('TABLE_ADV_SUB_MAIL', 'plugin_advsub_mail');
define('TABLE_ADV_SUB_MAIL_TYPE', 'plugin_advsub_mail_type');
define('TABLE_ADV_SUB_MAIL_STATUS', 'plugin_advsub_mail_status');
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(PLUGIN_PATH) . 'advancedsubscription/src/advanced_subscription_plugin.class.php';
