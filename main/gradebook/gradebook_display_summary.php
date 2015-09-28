<?php
/* For licensing terms, see /license.txt */

/**
 * Script
 * @package chamilo.gradebook
 */

$language_file = 'gradebook';

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_GRADEBOOK;

api_protect_course_script();

set_time_limit(0);
ini_set('max_execution_time', 0);
api_block_anonymous_users();

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$cat_id = isset($_GET['selectcat']) ? (int)$_GET['selectcat'] : null;
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
        foreach ($userList as $index => $value) {
            $pdfList[] = GradebookUtils::generateTable(
                $value['user_id'],
                $cat_id,
                false,
                true
            );
        }

        if (!empty($pdfList)) {
            // Print certificates (without the common header/footer/watermark
            //  stuff) and return as one multiple-pages PDF
            $pdf->html_to_pdf(
                $pdfList,
                null,
                null,
                false,
                false,
                true
            );
        }

        break;
    case 'download':
        $userId = isset($_GET['user_id']) && $_GET['user_id'] ? $_GET['user_id'] : null;
        GradebookUtils::generateTable($userId, $cat_id);
        break;
}

$course_code = api_get_course_id();

$interbreadcrumb[] = array('url' => Security::remove_XSS($_SESSION['gradebook_dest']).'?',	'name' => get_lang('Gradebook'));
$interbreadcrumb[] = array('url' => '#','name' => get_lang('GradebookListOfStudentsReports'));

$this_section = SECTION_COURSES;

Display::display_header('');

$token = Security::get_token();

echo Display::page_header(get_lang('GradebookListOfStudentsReports'));

echo '<div class="btn-group">';
if (count($userList) > 0) {
    $url = api_get_self().'?action=export_all&'.api_get_cidReq().'&selectcat='.$cat_id;
    echo Display::url(get_lang('ExportAllToPDF'), $url, array('class' => 'btn btn-default'));
}
echo '</div>';

if (count($userList) == 0 ) {
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
