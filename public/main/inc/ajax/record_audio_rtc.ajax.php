<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$courseInfo = api_get_course_info();
/** @var string $tool document or exercise */
$tool = $_REQUEST['tool'] ?? '';
$type = $_REQUEST['type'] ?? 'document'; // can be document or message

if ('document' === $type) {
    api_protect_course_script();
}

$userId = api_get_user_id();

if (!isset($_FILES['audio_blob'], $_REQUEST['audio_dir'])) {
    if ('exercise' === $tool) {
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => Display::return_message(
                get_lang('Upload failed, please check maximum file size limits and folder rights.'),
                'error'
            ),
        ]);

        //Display::cleanFlashMessages();
        exit;
    }

    Display::addFlash(
        Display::return_message(
            get_lang('Upload failed, please check maximum file size limits and folder rights.'),
            'error'
        )
    );
    exit;
}

$file = $_FILES['audio_blob'] ?? [];
$file['file'] = $file;
$audioDir = Security::remove_XSS($_REQUEST['audio_dir']);

switch ($type) {
    case 'document':
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

            $lpId = $_REQUEST['lp_id'] ?? null;
            $lpItemId = $_REQUEST['lp_item_id'] ?? null;

            $lpRepo = Container::getLpRepository();
            $lp = $lpRepo->find($lpId);
            if (!empty($lp) && empty($lpItemId)) {
                $lpItem = new learnpathItem($lpItemId);
                $lpItem->add_audio_from_documents($newDocId);
            }

            $data = DocumentManager::get_document_data_by_id($newDocId, $courseInfo['code']);

            if ('exercise' === $tool) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => $error,
                    //'message' => Display::getFlashToString(),
                    'fileUrl' => $data['document_url'],
                ]);
                exit;
            }

            echo $data['document_url'];
        }

        break;
    case 'message':
        if (isset($_FILES['audio_blob']['tmp_name'])) {
            $file['content'] = file_get_contents($_FILES['audio_blob']['tmp_name']);
            Session::write('current_audio', $file);
            echo 1;
        }
        break;
}
