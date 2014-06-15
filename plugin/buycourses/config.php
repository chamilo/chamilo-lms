<?php
/* For licensing terms, see /license.txt */

define('TABLE_BUY_COURSE', 'plugin_buy_course');
define('TABLE_BUY_COURSE_COUNTRY', 'plugin_buy_course_country');
define('TABLE_BUY_COURSE_PAYPAL', 'plugin_buy_course_paypal');
define('TABLE_BUY_COURSE_TRANSFERENCE', 'plugin_buy_course_transference');
define('TABLE_BUY_COURSE_TEMPORAL', 'plugin_buy_course_temporal');
define('TABLE_BUY_COURSE_SALE', 'plugin_buy_course_sale');

require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once  api_get_path(PLUGIN_PATH) . 'buy_courses/src/buy_course_plugin.class.php';
