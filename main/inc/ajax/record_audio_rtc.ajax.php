<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$courseInfo = api_get_course_info();
/** @var string $tool document or exercise */
$tool = isset($_REQUEST['tool']) ? $_REQUEST['tool'] : '';
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'document'; // can be document or message

if ($type === 'document') {
    api_protect_course_script();
}

$userId = api_get_user_id();

if (!isset($_FILES['audio_blob'], $_REQUEST['audio_dir'])) {
    if ($tool === 'exercise') {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => Display::return_message(get_lang('UploadError'), 'error'),
        ]);

        Display::cleanFlashMessages();
        exit;
    }

    Display::addFlash(Display::return_message(get_lang('UploadError'), 'error'));
    exit;
}

$file = isset($_FILES['audio_blob']) ? $_FILES['audio_blob'] : [];
$file['file'] = $file;
$audioDir = Security::remove_XSS($_REQUEST['audio_dir']);

switch ($type) {
    case 'document':
        $dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
        $saveDir = $dirBaseDocuments.$audioDir;
        if (!is_dir($saveDir)) {
            mkdir($saveDir, api_get_permissions_for_new_directories(), true);
        }

        if (empty($audioDir)) {
            $audioDir = '/';
        }

        $uploadedDocument = DocumentManager::upload_document(
            $file,
            $audioDir,
            $file['name'],
            null,
            0,
            'overwrite',
            false,
            in_array($tool, ['document', 'exercise']),
            'file',
            true,
            api_get_user_id(),
            $courseInfo,
            api_get_session_id(),
            api_get_group_id(),
            'exercise' === $tool
        );
        $error = empty($uploadedDocument) || !is_array($uploadedDocument);

        if (!$error) {
            $newDocId = $uploadedDocument['id'];
            $courseId = $uploadedDocument['c_id'];

            /** @var learnpath $lp */
            $lp = Session::read('oLP');
            $lpItemId = isset($_REQUEST['lp_item_id']) && !empty($_REQUEST['lp_item_id']) ? $_REQUEST['lp_item_id'] : null;
            if (!empty($lp) && empty($lpItemId)) {
                $lp->set_modified_on();

                $lpItem = new learnpathItem($lpItemId);
                $lpItem->add_audio_from_documents($newDocId);
            }

            $data = DocumentManager::get_document_data_by_id($newDocId, $courseInfo['code']);

            if ($tool === 'exercise') {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => $error,
                    'message' => Display::getFlashToString(),
                    'fileUrl' => $data['document_url'],
                ]);

                Display::cleanFlashMessages();
                exit;
            }

            echo $data['document_url'];
        }

        break;
    case 'message':
        Session::write('current_audio_id', $file['name']);
        api_upload_file('audio_message', $file, api_get_user_id());

        break;
}
