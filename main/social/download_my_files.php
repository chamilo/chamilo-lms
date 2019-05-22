<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$file = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($userId) || empty($file)) {
    exit;
}

$dir = UserManager::getUserPathById($userId, 'system');
if (empty($dir)) {
    exit;
}
$file = $dir.'/my_files/'.$file;

$config = api_get_configuration_value('block_my_files_access');

if ($config) {
    api_block_anonymous_users();
}

if (Security::check_abs_path($file, $dir.'my_files/')) {
    $result = DocumentManager::file_send_for_download($file);
    if ($result === false) {
        exit;
    }
}
