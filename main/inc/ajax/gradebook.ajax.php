<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_course_script(true);

$action = $_REQUEST['a'];

switch ($action) {
    case 'add_gradebook_comment':
        if (true !== api_get_configuration_value('allow_gradebook_comments')) {
            exit;
        }
        if (api_is_allowed_to_edit(null, true)) {
            $userId = $_REQUEST['user_id'] ?? 0;
            $gradeBookId = $_REQUEST['gradebook_id'] ?? 0;
            $comment = $_REQUEST['comment'] ?? '';
            GradebookUtils::saveComment($gradeBookId, $userId, $comment);
            echo 1;
            exit;
        }
        echo 0;
        break;
    case 'get_gradebook_weight':
        if (api_is_allowed_to_edit(null, true)) {
            $cat_id = $_GET['cat_id'];
            $cat = Category::load($cat_id);
            if ($cat && isset($cat[0])) {
                echo $cat[0]->get_weight();
            } else {
                echo 0;
            }
        }
        break; /*
    case 'generate_custom_report':
        if (api_is_allowed_to_edit(null, true)) {
            $allow = api_get_configuration_value('gradebook_custom_student_report');
            if (!$allow) {
                exit;
            }
            $form = new FormValidator(
                'search',
                'get',
                api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq().'&action=generate_custom_report'
            );
            $form->addText('custom_course_id', get_lang('CourseId'));
            $form->addDateRangePicker('range', get_lang('DateRange'));
            $form->addHidden('action', 'generate_custom_report');
            $form->addButtonSearch();
            $form->display();
        }
        break;*/
    case 'export_all_certificates':
        $categoryId = (int) $_GET['cat_id'];
        $filterOfficialCodeGet = isset($_GET['filter']) ? Security::remove_XSS($_GET['filter']) : null;

        if (api_is_student_boss()) {
            $userGroup = new UserGroup();
            $userList = $userGroup->getGroupUsersByUser(api_get_user_id());
        } else {
            $userList = [];
            if (!empty($filterOfficialCodeGet)) {
                $userList = UserManager::getUsersByOfficialCode($filterOfficialCodeGet);
            }
        }

        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();

        $commandScript = api_get_path(SYS_CODE_PATH).'gradebook/cli/export_all_certificates.php';

        $userList = implode(',', $userList);

        shell_exec("php $commandScript $courseCode $sessionId $categoryId $userList > /dev/null &");
        break;
    case 'verify_export_all_certificates':
        $categoryId = (int) $_GET['cat_id'];
        $courseCode = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : api_get_course_id();
        $sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : api_get_session_id();
        $date = api_get_utc_datetime(null, false, true);

        $pdfName = 'certs_'.$courseCode.'_'.$sessionId.'_'.$categoryId.'_'.$date->format('Y-m-d');

        $sysFinalFile = api_get_path(SYS_ARCHIVE_PATH)."$pdfName.pdf";
        $webFinalFile = api_get_path(WEB_ARCHIVE_PATH)."$pdfName.pdf";

        if (file_exists($sysFinalFile)) {
            echo $webFinalFile;
        }
        break;
    default:
        echo '';
        break;
}
exit;
