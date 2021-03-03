<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$isAllowedToEdit = api_is_allowed_to_edit();
$courseInfo = api_get_course_info();
$courseEntity = api_get_course_entity();

switch ($action) {
    case 'show_student_work':
        api_protect_course_script(true);
        if ($isAllowedToEdit) {
            $itemList = isset($_REQUEST['item_list']) ? $_REQUEST['item_list'] : [];
            $itemList = explode(',', $itemList);
            if (!empty($itemList)) {
                foreach ($itemList as $itemId) {
                    makeVisible($itemId, $courseInfo);
                }
                echo '1';
                exit;
            }
        }
        echo '0';
        break;
    case 'hide_student_work':
        api_protect_course_script(true);
        if ($isAllowedToEdit) {
            $itemList = isset($_REQUEST['item_list']) ? $_REQUEST['item_list'] : [];
            $itemList = explode(',', $itemList);
            if (!empty($itemList)) {
                foreach ($itemList as $itemId) {
                    makeInvisible($itemId, $courseInfo);
                }
                echo '1';
                exit;
            }
        }
        echo '0';
        break;
    case 'delete_student_work':
        api_protect_course_script(true);
        if ($isAllowedToEdit) {
            if (empty($_REQUEST['id'])) {
                return false;
            }
            $itemList = explode(',', $_REQUEST['id']);
            foreach ($itemList as $itemId) {
                deleteWorkItem($itemId, $courseInfo);
            }
            echo '1';
            exit;
        }
        echo '0';
        break;
    case 'upload_file':
        api_protect_course_script(true);
        $workId = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

        $workInfo = get_work_data_by_id($workId);
        $sessionId = api_get_session_id();
        $userId = api_get_user_id();
        $groupId = api_get_group_id();

        $onlyOnePublication = api_get_configuration_value('allow_only_one_student_publication_per_user');
        if ($onlyOnePublication) {
            $count = get_work_count_by_student($userId, $workId);
            if ($count >= 1) {
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
            $router = Container::getRouter();
            $resultList = [];
            foreach ($fileList as $file) {
                $globalFile = [];
                $globalFile['files'] = $file;

                $values = [
                    'contains_file' => 1,
                    'title' => $file['name'],
                    'description' => '',
                ];

                $studentPublication = processWorkForm(
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

                $json = [];
                if (null !== $studentPublication) {
                    $url = $router->generate('chamilo_core_resource_download',
                        [
                            'id' => $studentPublication->getResourceNode()->getId(),
                            'tool' => 'student_publication',
                            'type' => 'student_publications',
                        ]
                    ).'?'.api_get_cidreq();
                    $json['name'] = api_htmlentities($studentPublication->getTitle());
                    $json['link'] = Display::url(
                        api_htmlentities($studentPublication->getTitle()),
                        api_htmlentities($url),
                        ['target' => '_blank']
                    );

                    $json['url'] = $url;
                    $json['size'] = '';
                    //$json['type'] = api_htmlentities($result['filetype']);
                    $json['result'] = Display::return_icon(
                        'accept.png',
                        get_lang('Uploaded..')
                    );
                } else {
                    $json['url'] = '';
                    $json['error'] = get_lang('Error');
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

        $result = [];

        if (!empty($_FILES) && !empty($itemId)) {
            $file = $_FILES['file'];

            $courseInfo = api_get_course_info();
            $workInfo = get_work_data_by_id($itemId);
            $workInfoParent = get_work_data_by_id($workInfo['parent_id']);
            $resultUpload = uploadWork($workInfoParent, $courseEntity, true, $workInfo);
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

                $json = [];
                $json['name'] = Display::url(
                    api_htmlentities($result['title']),
                    api_htmlentities($result['url']),
                    ['target' => '_blank']
                );

                $json['type'] = api_htmlentities($file['type']);
                $json['size'] = format_file_size($file['size']);
            }

            if (isset($result['url'])) {
                $json['result'] = Display::return_icon(
                    'accept.png',
                    get_lang('Uploaded..'),
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
