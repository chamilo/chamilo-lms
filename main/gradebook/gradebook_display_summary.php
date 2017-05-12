<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script
 * @package chamilo.gradebook
 */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script();

set_time_limit(0);
ini_set('max_execution_time', 0);
api_block_anonymous_users();

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$cat_id = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : null;
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;

$userList = CourseManager::get_user_list_from_course_code(
    api_get_course_id(),
    api_get_session_id()
);

switch ($action) {
    case 'export_all':
        $params = array();
        $pdf = new PDF('A4', 'P', $params);

        $pdfList = array();
        $cats = Category::load($cat_id, null, null, null, null, null, false);

        $session_id = api_get_session_id();
        if (empty($session_id)) {
            $statusToFilter = STUDENT;
        } else {
            $statusToFilter = 0;
        }

        $studentList = CourseManager::get_user_list_from_course_code(
            api_get_course_id(),
            $session_id,
            null,
            null,
            $statusToFilter
        );

        $tpl = new Template('', false, false, false);

        $courseInfo = api_get_course_info();
        $params = array(
            'pdf_title' => sprintf(get_lang('GradeFromX'), $courseInfo['name']),
            'session_info' => '',
            'course_info' => '',
            'pdf_date' => '',
            'course_code' => api_get_course_id(),
            'student_info' => null,
            'show_grade_generated_date' => true,
            'show_real_course_teachers' => false,
            'show_teacher_as_myself' => false
        );

        $pdf = new PDF('A4', $params['orientation'], $params, $tpl);

        foreach ($userList as $index => $value) {
            $pdfList[] = GradebookUtils::generateTable(
                $value['user_id'],
                $cats,
                false,
                true,
                $studentList,
                $pdf
            );
        }

        if (!empty($pdfList)) {
            // Print certificates (without the common header/footer/watermark
            //  stuff) and return as one multiple-pages PDF
            $address = api_get_setting('institution_address');
            $phone = api_get_setting('administratorTelephone');
            $address = str_replace('\n', '<br />', $address);
            $pdf->custom_header = array('html' => "<h5 align='right'>$address <br />$phone</h5>");
            //  stuff) and return as one multiple-pages PDF
            $pdf->html_to_pdf(
                $pdfList,
                null,
                null,
                false,
                true,
                true
            );
        }

        // Delete calc_score session data
        Session::erase('calc_score');

        break;
    case 'download':
        $userId = isset($_GET['user_id']) && $_GET['user_id'] ? $_GET['user_id'] : null;
        $cats = Category::load($cat_id, null, null, null, null, null, false);
        GradebookUtils::generateTable($userId, $cats);
        break;
}

$course_code = api_get_course_id();

$interbreadcrumb[] = array('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?', 'name' => get_lang('Gradebook'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('GradebookListOfStudentsReports'));

$this_section = SECTION_COURSES;

Display::display_header('');

$token = Security::get_token();

echo Display::page_header(get_lang('GradebookListOfStudentsReports'));

echo '<div class="btn-group">';
if (count($userList) > 0) {
    $url = api_get_self().'?action=export_all&'.api_get_cidreq().'&selectcat='.$cat_id;
    echo Display::url(get_lang('ExportAllToPDF'), $url, array('class' => 'btn btn-default'));
}
echo '</div>';

if (count($userList) == 0) {
    echo Display::display_warning_message(get_lang('NoResultsAvailable'));
} else {
    echo '<br /><br /><table class="data_table">';
    foreach ($userList as $index => $value) {
        echo '<tr>
                <td width="100%" >'.
                get_lang('Student').' : '.api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].') </td>';
        echo '<td>';
        $url = api_get_self().'?'.api_get_cidreq().'&action=download&user_id='.$value['user_id'].'&selectcat='.$cat_id;
        $link = Display::url(
            get_lang('ExportToPDF'),
            $url,
            array('target' => '_blank', 'class' => 'btn btn-default')
        );
        echo $link;
        echo '</td></tr>';
    }
    echo '</table>';
}

Display::display_footer();
