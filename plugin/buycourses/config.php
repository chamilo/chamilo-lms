<?php
/* For licensing terms, see /license.txt */

define('TABLE_BUY_SESSION', 'plugin_buy_session');
define('TABLE_BUY_SESSION_COURSE', 'plugin_buy_session_course');
define('TABLE_BUY_SESSION_TEMPORAL', 'plugin_buy_session_temporal');
define('TABLE_BUY_SESSION_SALE', 'plugin_buy_session_sale');
define('TABLE_BUY_COURSE', 'plugin_buy_course');
define('TABLE_BUY_COURSE_COUNTRY', 'plugin_buy_course_country');
define('TABLE_BUY_COURSE_PAYPAL', 'plugin_buy_course_paypal');
define('TABLE_BUY_COURSE_TRANSFER', 'plugin_buy_course_transfer');
define('TABLE_BUY_COURSE_TEMPORAL', 'plugin_buy_course_temporal');
define('TABLE_BUY_COURSE_SALE', 'plugin_buy_course_sale');

require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'plugin.class.php';
require_once  api_get_path(PLUGIN_PATH) . 'buycourses/src/buy_course_plugin.class.php';
