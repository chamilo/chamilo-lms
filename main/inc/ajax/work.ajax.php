<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once __DIR__.'/../global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
    case 'upload_file':
        api_protect_course_script(true);
        $workId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

        $workInfo = get_work_data_by_id($workId);
        $courseInfo = api_get_course_info();
        $sessionId = api_get_session_id();
        $userId = api_get_user_id();
        $groupId = api_get_group_id();

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

                $values = [
                    'contains_file' => 1,
                    'title' => $file['name'],
                    'description' => ''
                ];

                $result = processWorkForm(
                    $workInfo,
                    $values,
                    $courseInfo,
                    $sessionId,
                    $groupId,
                    $userId,
                    $file,
                    api_get_configuration_value('assignment_prevent_duplicate_upload'),
                    false
                );

                $json = array();
                if (!empty($result) && is_array($result) && empty($result['error'])) {
                    $json['name'] = Display::url(
                        api_htmlentities($result['title']),
                        api_htmlentities($result['view_url']),
                        array('target' => '_blank')
                    );

                    $json['url'] = $result['view_url'];
                    $json['size'] = '';
                    $json['type'] = api_htmlentities($result['filetype']);
                    $json['result'] = Display::return_icon(
                        'accept.png',
                        get_lang('Uploaded')
                    );
                } else {
                    $json['url'] = '';
                    $json['error'] = isset($result['error']) ? $result['error'] : get_lang('Error');
                }
                $resultList[] = $json;
            }

            echo json_encode(['files' => $resultList]);
        }

        break;
    case 'delete_work':
        if ($isAllowedToEdit) {
            if (empty($_REQUEST['id'])) {
                return false;
            }
            $workList = explode(',', $_REQUEST['id']);
            foreach ($workList as $workId) {
                deleteDirWork($workId);
            }
        }
        break;
    case 'upload_correction_file':
        api_protect_course_script(true);
        // User access same as upload.php
        $is_allowed_to_edit = api_is_allowed_to_edit(null, true);
        $itemId = isset($_GET['item_id']) ? intval($_GET['item_id']) : '';

        $result = array();

        if (!empty($_FILES) && !empty($itemId)) {
            $file = $_FILES['file'];

            $courseInfo = api_get_course_info();
            $workInfo = get_work_data_by_id($itemId);
            $workInfoParent = get_work_data_by_id($workInfo['parent_id']);
            $resultUpload = uploadWork($workInfoParent, $courseInfo, true, $workInfo);
            if (!$resultUpload) {
                echo 'false';
                break;
            }
            $work_table = Database::get_course_table(
                TABLE_STUDENT_PUBLICATION
            );

            if (isset($resultUpload['url']) && !empty($resultUpload['url'])) {
                $title = isset($resultUpload['filename']) && !empty($resultUpload['filename']) ? $resultUpload['filename'] : get_lang('Untitled');
                $url = Database::escape_string($resultUpload['url']);
                $title = Database::escape_string($title);

                $sql = "UPDATE $work_table SET
                            url_correction = '".$url."',
                            title_correction = '".$title."'
                        WHERE iid = $itemId";
                Database::query($sql);

                $result['title'] = $resultUpload['filename'];
                $result['url'] = 'view.php?'.api_get_cidreq().'&id='.$itemId;

                $json = array();
                $json['name'] = Display::url(
                    api_htmlentities($result['title']),
                    api_htmlentities($result['url']),
                    array('target' => '_blank')
                );

                $json['type'] = api_htmlentities($file['type']);
                $json['size'] = format_file_size($file['size']);
            }

            if (isset($result['url'])) {
                $json['result'] = Display::return_icon(
                    'accept.png',
                    get_lang('Uploaded'),
                    [],
                    ICON_SIZE_TINY
                );
            } else {
                $json['result'] = Display::return_icon(
                    'exclamation.png',
                    get_lang('Error'),
                    [],
                    ICON_SIZE_TINY
                );
            }

            header('Content-Type: application/json');
            echo json_encode($json);
        }
        break;
    default:
        echo '';
        break;
}
exit;
