<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users();

$plugin = Justification::create();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    api_not_allowed(true);
}

$userJustification = $plugin->getUserJustification($id);
if (!$userJustification) {
    api_not_allowed(true);
}

$currentUserId = api_get_user_id();
$canManageUsers = api_is_platform_admin() || ($plugin->canSessionAdminsManageUsers() && api_is_session_admin());

if (!$canManageUsers && (int) $userJustification['user_id'] !== (int) $currentUserId) {
    api_not_allowed(true);
}

$filePath = $plugin->getUserJustificationFileSystemPath($userJustification);
if (!$filePath || !is_file($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo get_lang('File not found');

    exit;
}

$fileName = basename((string) $userJustification['file_path']);
$mimeType = 'application/octet-stream';

if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMimeType = $finfo->file($filePath);
    if ($detectedMimeType) {
        $mimeType = $detectedMimeType;
    }
}

header('Content-Type: '.$mimeType);
header('Content-Length: '.filesize($filePath));
header('Content-Disposition: attachment; filename="'.str_replace('"', '', $fileName).'"');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
