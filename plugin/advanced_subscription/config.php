<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author Daniel Alejandro Barreto Alva <daniel.barreto@beeznest.com>
 * @package chamilo.plugin.advanced_subscription
 */

define('TABLE_ADV_SUB_QUEUE', 'plugin_advsub_queue');

define('ADV_SUB_ACTION_STUDENT_REQUEST', 0);
define('ADV_SUB_ACTION_SUPERIOR_APPROVE', 1);
define('ADV_SUB_ACTION_SUPERIOR_DISAPPROVE', 2);
define('ADV_SUB_ACTION_SUPERIOR_SELECT', 3);
define('ADV_SUB_ACTION_ADMIN_APPROVE', 4);
define('ADV_SUB_ACTION_ADMIN_DISAPPROVE', 5);
define('ADV_SUB_ACTION_STUDENT_REQUEST_NO_BOSS', 6);

define('ADV_SUB_QUEUE_STATUS_NO_QUEUE', -1);
define('ADV_SUB_QUEUE_STATUS_START', 0);
define('ADV_SUB_QUEUE_STATUS_BOSS_DISAPPROVED', 1);
define('ADV_SUB_QUEUE_STATUS_BOSS_APPROVED', 2);
define('ADV_SUB_QUEUE_STATUS_ADMIN_DISAPPROVED', 3);
define('ADV_SUB_QUEUE_STATUS_ADMIN_APPROVED', 10);

require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once api_get_path(PLUGIN_PATH) . 'advanced_subscription/src/AdvancedSubscriptionPlugin.php';
require_once api_get_path(PLUGIN_PATH) . 'advanced_subscription/src/HookAdvancedSubscription.php';
