<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls for the document upload.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'];
switch ($action) {
    case 'get_dir_size':
        api_protect_course_script(true);
        $path = isset($_GET['path']) ? $_GET['path'] : '';
        $isAllowedToEdit = api_is_allowed_to_edit();
        $size = DocumentManager::getTotalFolderSize($path, $isAllowedToEdit);
        echo format_file_size($size);
        break;
    case 'get_document_quota':
        // Getting the course quota
        $courseQuota = DocumentManager::get_course_quota();

        // Calculating the total space
        $total = DocumentManager::documents_total_space(api_get_course_int_id());

        // Displaying the quota
        echo DocumentManager::displaySimpleQuota($courseQuota, $total);
        break;
    case 'upload_file':
        api_protect_course_script(true);
        // User access same as upload.php
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);

        $sessionId = api_get_session_id();

        if (!$is_allowed_to_edit && $sessionId && $_REQUEST['curdirpath'] == "/basic-course-documents__{$sessionId}__0") {
            $session = SessionManager::fetch($sessionId);

            if (!empty($session) && $session['session_admin_id'] == api_get_user_id()) {
                $is_allowed_to_edit = true;
            }
        }

        // This needs cleaning!
        if (api_get_group_id()) {
            $groupInfo = GroupManager::get_group_properties(api_get_group_id());
            // Only course admin or group members allowed
            if ($is_allowed_to_edit || GroupManager::is_user_in_group(api_get_user_id(), $groupInfo)) {
                if (!GroupManager::allowUploadEditDocument(api_get_user_id(), api_get_course_int_id(), $groupInfo)) {
                    exit;
                }
            } else {
                exit;
            }
        } elseif ($is_allowed_to_edit ||
            DocumentManager::is_my_shared_folder(api_get_user_id(), $_REQUEST['curdirpath'], api_get_session_id())
        ) {
            // ??
        } else {
            // No course admin and no group member...
            exit;
        }

        $directoryParentId = isset($_POST['directory_parent_id']) ? $_POST['directory_parent_id'] : 0;
        $currentDirectory = '';
        if (empty($directoryParentId)) {
            $currentDirectory = isset($_REQUEST['curdirpath']) ? $_REQUEST['curdirpath'] : '';
        } else {
            $documentData = DocumentManager::get_document_data_by_id($directoryParentId, api_get_course_id());
            if ($documentData) {
                $currentDirectory = $documentData['path'];
            }
        }

        $ifExists = isset($_POST['if_exists']) ? $_POST['if_exists'] : '';
        $unzip = isset($_POST['unzip']) ? 1 : 0;

        if (empty($ifExists)) {
            $fileExistsOption = api_get_setting('document_if_file_exists_option');
            $defaultFileExistsOption = 'rename';
            if (!empty($fileExistsOption)) {
                $defaultFileExistsOption = $fileExistsOption;
            }
        } else {
            $defaultFileExistsOption = $ifExists;
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
                $result = DocumentManager::upload_document(
                    $globalFile,
                    $currentDirectory,
                    '',
                    '', // comment
                    $unzip,
                    $defaultFileExistsOption,
                    false,
                    false,
                    'files'
                );

                $json = [];
                if (!empty($result) && is_array($result)) {
                    $json['name'] = api_htmlentities($result['title']);
                    $json['link'] = Display::url(
                        api_htmlentities($result['title']),
                        api_htmlentities($result['url']),
                        ['target' => '_blank']
                    );
                    $json['url'] = $result['url'];
                    $json['size'] = format_file_size($file['size']);
                    $json['type'] = api_htmlentities($file['type']);
                    $json['result'] = Display::return_icon(
                        'accept.png',
                        get_lang('Uploaded')
                    );
                } else {
                    $json['name'] = isset($file['name']) ? $file['name'] : get_lang('Unknown');
                    $json['url'] = '';
                    $json['error'] = get_lang('Error');
                }
                $resultList[] = $json;
            }

            echo json_encode(['files' => $resultList]);
        }
        exit;
        break;
    case 'document_preview':
        $courseInfo = api_get_course_info_by_id($_REQUEST['course_id']);
        if (!empty($courseInfo) && is_array($courseInfo)) {
            echo DocumentManager::get_document_preview(
                $courseInfo,
                false,
                '_blank',
                $_REQUEST['session_id']
            );
        }
        break;
    case 'document_destination':
        //obtained the bootstrap-select selected value via ajax
        $dirValue = isset($_POST['dirValue']) ? $_POST['dirValue'] : null;
        echo Security::remove_XSS($dirValue);
        break;
}
exit;
