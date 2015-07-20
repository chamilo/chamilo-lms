<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
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

            $work_table = Database:: get_course_table(
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
                    get_lang('Uploaded')
                );
            } else {
                $json['result'] = Display::return_icon(
                    'exclamation.png',
                    get_lang('Error')
                );
            }
            echo json_encode($json);
        }

        break;
    default:
        echo '';
        break;
}
exit;
