<?php

/* For licensing terms, see /license.txt */

/**
 * Sub language AJAX script to update variables.
 */
require_once __DIR__ . '/../inc/global.inc.php';

api_protect_admin_script();

if (isset($_POST['content'], $_POST['filename'], $_POST['msgidEncoded'])) {
    $content = $_POST['content'];
    $filename = $_POST['filename'];
    $msgid = base64_decode($_POST['msgidEncoded']);

    $response = SubLanguageManager::updateOrAddMsgid($filename, $msgid, $content);
    echo json_encode($response);
} else {
    echo json_encode(["success" => false, "error" => get_lang('POST data missing')]);
}
