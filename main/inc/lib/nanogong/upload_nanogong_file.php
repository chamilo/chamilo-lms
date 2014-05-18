<?php
/* For licensing terms, see /license.txt */

/**
 *    This file allows creating new svg and png documents with an online editor.
 *
 * @package chamilo.document
 *
 * @author Juan Carlos Raña Trabado
 * @since 5/mar/2011
 */
/**
 * Code
 */
require_once '../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

api_protect_course_script();
api_block_anonymous_users();

if (!isset($_GET['filename']) || !isset($_GET['file_field'])) {
    api_not_allowed(false);
    exit;
}

$courseInfo = api_get_course_info();

$fileUpload = null;
if (is_uploaded_file($_FILES[$_GET['file_field']]['tmp_name'])) {
    $fileUpload = $_FILES[$_GET['file_field']];
} else {
    exit;
}

$output = false;
$documentData = DocumentManager::upload_document($_FILES, $_GET['path'], null, null, 0, 'overwrite', false, $output);

if (!empty($documentData)) {
    $newDocId = $documentData['id'];
    $newMp3DocumentId = DocumentManager::addAndConvertWavToMp3($documentData, $courseInfo, api_get_user_id());
    if ($newMp3DocumentId) {
        $newDocId = $newMp3DocumentId;
    }

    if (isset($_REQUEST['lp_item_id']) && !empty($_REQUEST['lp_item_id'])) {
        $lpItemId = $_REQUEST['lp_item_id'];
        /** @var learnpath $lp */
        $lp = isset($_SESSION['oLP']) ? $_SESSION['oLP'] : null;

        if (!empty($lp)) {
            $lp->set_modified_on();
            $lpItem = new learnpathItem($lpItemId);
            $lpItem->add_audio_from_documents($newDocId);
        }
    }
}