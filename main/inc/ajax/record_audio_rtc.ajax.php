<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';

// Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

$courseInfo = api_get_course_info();

if (!isset($_FILES['audio_blob'], $_REQUEST['audio_dir'])) {
    api_not_allowed();
}

$file = $_FILES['audio_blob'];
$audioDir = Security::remove_XSS($_REQUEST['audio_dir']);
$userId = api_get_user_id();

if (empty($userId)) {
    api_not_allowed();
}

$audioFileName = Security::remove_XSS($file['name']);
$audioFileName = Database::escape_string($audioFileName);
$audioFileName = api_replace_dangerous_char($audioFileName);
$audioFileName = disable_dangerous_file($audioFileName);
$audioDir = Security::remove_XSS($audioDir);

$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
$saveDir = $dirBaseDocuments.$audioDir;

if (!is_dir($saveDir)) {
    DocumentManager::createDefaultAudioFolder($courseInfo);
}

$documentPath = $saveDir.'/'.$audioFileName;

$file['file'] = $file;

$result = DocumentManager::upload_document(
    $file,
    $audioDir,
    $file['name'],
    null,
    0,
    'overwrite',
    false,
    false
);

if (!empty($result) && is_array($result)) {
    $newDocId = $result['id'];
    $courseId = $result['c_id'];

    /** @var learnpath $lp */
    $lp = Session::read('oLP');
    $lpItemId = isset($_REQUEST['lp_item_id']) && !empty($_REQUEST['lp_item_id']) ? $_REQUEST['lp_item_id'] : null;
    if (!empty($lp) && empty($lpItemId)) {
        $lp->set_modified_on();

        $lpItem = new learnpathItem($lpItemId);
        $lpItem->add_audio_from_documents($newDocId);
    }

    $data = DocumentManager::get_document_data_by_id($newDocId, $courseInfo['code']);

    Display::addFlash(
        Display::return_message(get_lang('DocumentCreated'), 'success')
    );

    echo $data['document_url'];
    exit;
}
