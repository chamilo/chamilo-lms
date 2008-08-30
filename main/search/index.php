<?php
/*
 * This file includes lp_list_search to avoid duplication of code, it
 * bootstraps dokeos api enough to make lp_list_search work.
 */
include_once ('../inc/global.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
include_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');

api_block_anonymous_users(); // only users who are logged in can proceed

$search_action = 'index.php';
require '../newscorm/lp_list_search.php';
?>
