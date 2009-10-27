<?php
/* For licensing terms, see /dokeos_license.txt */
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
$field_id = intval($_GET['field_id']);
$tag = $_GET['tag'];
echo UserManager::get_tags($tag, $field_id,'json','10');