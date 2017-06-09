<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls for the document upload
 */
require_once __DIR__.'/../global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'dropbox/dropbox_functions.inc.php';

$action = $_REQUEST['a'];
switch ($action) {
    case 'upload_file':
        api_protect_course_script(true);
        // User access same as upload.php
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

        $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (empty($recipients) && empty($id)) {
            $resultList[] = ['error' => get_lang('YouMustSelectAtLeastOneDestinee')];
            echo json_encode(['files' => $resultList]);
            exit;
        }
        $work = null;
        if (!empty($id)) {
            $work = new Dropbox_SentWork($id);
            if (empty($work)) {
                $resultList[] = ['error' => get_lang('Error')];
                echo json_encode(['files' => $resultList]);
                exit;
            }
        }

        if (!empty($_FILES)) {
            $files = $_FILES['files'];
            $fileList = [];
            foreach ($files as $name => $array) {
                $counter = 0;
                foreach ($array as $data) {
                    $fileList[$counter][$name] = $data;
                    $counter++;
                }
            }

            $resultList = [];
            foreach ($fileList as $file) {
                $globalFile = [];
                $globalFile['files'] = $file;
                /** @var Dropbox_SentWork $result */
                $result = store_add_dropbox($file, $work);

                $json = array();
                if (!empty($result)) {
                    $json['name'] = Display::url(
                        api_htmlentities($result->title),
                        api_htmlentities(api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq()),
                        array('target' => '_blank')
                    );

                    $json['url'] = api_get_path(WEB_CODE_PATH).'dropbox/index.php?'.api_get_cidreq();
                    $json['size'] = format_file_size($result->filesize);
                    $json['type'] = api_htmlentities($file['type']);
                    $json['result'] = Display::return_icon(
                        'accept.png',
                        get_lang('Uploaded')
                    );
                } else {
                    $json['result'] = Display::return_icon(
                        'exclamation.png',
                        get_lang('Error')
                    );
                }
                $resultList[] = $json;
            }

            echo json_encode(['files' => $resultList]);
        }
        exit;
        break;
}
exit;
