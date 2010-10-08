<?php
/* For licensing terms, see /license.txt */

/**
 * Con este archivo se editan, se validan, se rechazan o se solicita mas informacion de los cursos que se solicitaron por parte de los profesores y que estan añadidos en la tabla temporal.
 * A list containig the pending course requests
 * @package chamilo.admin
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * Centro de Supercomputacion de Galicia (CESGA)
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com> (technical adaptation for Chamilo 1.8.8), 2010
 */

/* INIT SECTION */

// Language files that need to be included.
$language_file = array('admin', 'create_course');

$cidReset = true;
require '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'course_info.conf.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'course_request.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

// Including a configuration file.
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

// Filltering passed to this page parameters.
$accept_course_request = intval($_GET['accept_course_request']);
$reject_course_request = intval($_GET['reject_course_request']);
$request_info = intval($_GET['request_info']);

/**
 * Coutse acceptance and creation.
 */
if (!empty($accept_course_request)) {

    $course_id = CourseRequestManager::accept_course_request($accept_course_request);

    if ($course_id) {
        // TODO: Prepare a confirmation message.
    } else {
        // Prepare an error message.
    }

}

/**
 * Course rejection
 */
if (isset($_GET['reject_course_request']) && $_GET['reject_course_request'] != '') {

    $result = CourseRequestManager::reject_course_request($reject_course_request);

    if ($result) {
        // TODO: Prepare a confirmation message.
    } else {
        // Prepare an error message.
    }
}

/**
 * Sending to the teacher a request for additional information about the proposed course.
 */
if (!empty($request_info)) {
    CourseRequestManager::ask_for_additional_info($request_info);
}



/**
 * Get the number of courses which will be displayed.
 */
function get_number_of_courses() {
    return CourseRequestManager::count_course_requests(COURSE_REQUEST_PENDING);
}

/**
 * Get course data to display
 */
function get_course_data($from, $number_of_items, $column, $direction) {
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $users_table = Database :: get_main_table(TABLE_MAIN_USER);
    $course_users_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

    $sql = "SELECT code AS col0,
                   code AS col1,
                   title AS col2,
                   category_code AS col3,
                   tutor_name AS col4,
                   request_date AS col5,
                   id  AS col6
                   FROM $course_table WHERE status = ".COURSE_REQUEST_PENDING;

    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database::query($sql);
    $courses = array();

    while ($course = Database::fetch_row($res)) {
        $courses[] = $course;
    }

    return $courses;
}

/**
 * Enlace a la ficha del profesor
 */
function email_filter($teacher) {
    $sql = "SELECT user_id FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE tutor_name LIKE '".$teacher."'";
    $res = Database::query($sql);
    $info = Database::fetch_array($res);
    return '<a href="./user_information.php?user_id='.$info[0].'">'.$teacher.'</a>';
}

/**
 * Actions in the list: edit, accept, reject, request additional information.
 */
function modify_filter($id) {
    /*
    return
    '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.' '.'<a href="?reject_course_request='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><img src="../img/delete.gif" border="0" style="vertical-align: middle" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'"/></a>'.'  '.'<a href="?request_info='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('AdditionalInfoWillBeAsked'), ENT_QUOTES))."'".')) return false;"><img src="../img/cesga_question.gif" border="0" style="vertical-align: middle" title="'.get_lang('AskAdditionalInfo').'" alt="'.get_lang('AskAdditionalInfo').'"/></a>&nbsp;'.'  '.'<a href="?accept_course_request='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ANewCourseWillBeCreated'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('AcceptThisCourseRequest').'" alt="'.get_lang('AcceptThisCourseRequest').'"/></a>&nbsp;';
    */
    $sql_request_info = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE (id = ".$id." AND info = 1)";
    $res_request_info = Database::query($sql_request_info);

    if (Database::num_rows($res_request_info) > 0) { //Si ya se le ha pedido información, no se muestra esa opción

        return
            '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.' '.'<a href="?reject_course_request='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><img src="../img/delete.gif" border="0" style="vertical-align: middle" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'"/></a>'.'  '.'<a href="?accept_course_request='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ANewCourseWillBeCreated'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('AcceptThisCourseRequest').'" alt="'.get_lang('AcceptThisCourseRequest').'"/></a>&nbsp;';

    } else {

        return
            '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.'  '.'<a href="?reject_course_request='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><img src="../img/delete.gif" border="0" style="vertical-align: middle" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'"/></a>'.'  '.'<a href="?accept_course_request='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ANewCourseWillBeCreated'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('AcceptThisCourseRequest').'" alt="'.get_lang('AcceptThisCourseRequest').'"/></a>'.'  '.'<a href="?request_info='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('AdditionalInfoWillBeAsked'), ENT_QUOTES))."'".')) return false;"><img src="../img/cesga_question.gif" border="0" style="vertical-align: middle" title="'.get_lang('AskAdditionalInfo').'" alt="'.get_lang('AskAdditionalInfo').'"/></a>&nbsp;&nbsp;';
    }
}

if (isset ($_POST['action'])) {
    switch ($_POST['action']) {
        // Delete selected courses
        case 'delete_courses' :
            $course_codes = $_POST['course'];
            if (count($course_codes) > 0) {
                foreach ($course_codes as $index => $course_code) {
                    //CourseManager :: delete_course($course_code);
                    $sql = "DELETE FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE code LIKE '".$course_code."'";
                    //echo $sql;
                    $result = Database::query($sql);
                }
            }
            break;
    }
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('ReviewCourseRequests');
Display :: display_header($tool_name);

//api_display_tool_title($tool_name);
if (isset ($_GET['delete_course'])) {
    //CourseManager :: delete_course($_GET['delete_course']);
}

// Create a sortable table with the course data
$table = new SortableTable('courses', 'get_number_of_courses', 'get_course_data', 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);
$table->set_header(1, get_lang('Code'));
$table->set_header(2, get_lang('Title'));
$table->set_header(3, get_lang('Category'));
//$table->set_header(4, get_lang('Teacher'), false);
//$table->set_header(5, get_lang('CourseRequestDate'), false);
$table->set_header(4, get_lang('Teacher'));
$table->set_header(5, get_lang('CourseRequestDate'));
$table->set_header(6, '', false);
$table->set_column_filter(4,'email_filter');
$table->set_column_filter(6,'modify_filter');
$table->set_form_actions(array('delete_courses' => get_lang('DeleteCourse')), 'course');
$table->display();

/* FOOTER */

Display :: display_footer();
