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
require_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');

// Information about the helpdesk.
$emailto_rt = "helpdesk_elearning@cesga.es";
$emailbody_rt = "Owner: e-learning\nStatus: resolved\n\n";
$email_send_rt = get_setting('administratorName').' '.get_setting('administratorSurname');
$email_send2_rt = get_setting('emailAdministrator');

/*
 * Sending to the teacher a request for additional information about the proposed course.
 */

if (isset($_GET['request_info']) && $_GET['request_info'] != '') {

    // TODO: Sent the e-mail.

    // Marking the fact, that additional information has been requested.
    $sql_info = "UPDATE ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." SET info = 1 WHERE id LIKE '".$_GET['request_info']."'";
    $result_info = Database::query($sql_info);

    unset ($_GET['request_info']);
}

/**
 * Course rejection
 */

if (isset($_GET['reject_course']) && $_GET['reject_course'] != '') {

    // TODO: Send the e-mail.

    //Una vez notificado al profesor, cambiamos el estado del curso en la tabla temporal
    $sql_borrar = "UPDATE ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." SET status = ".COURSE_REQUEST_REJECTED." WHERE id LIKE '".$_GET['reject_course']."'";
    $result_borrar = Database::query($sql_borrar);

    unset ($_GET['reject_course']);
}

/**
 * Coutse acceptance and creation.
 */

if (isset($_GET['accept_course']) && $_GET['accept_course'] != '') {

    // TODO: Filter $_GET['accept_course']
    $course_id = CourseRequestManager::accept_course_request($_GET['accept_course']);

    if ($course_id) {
        // TODO: Prepare a confirmation message.
    } else {
        // Prepare an error message.
    }

    // TODO: Send the e-mail.

    unset ($_GET['accept_course']);
}

/**
 * Funcion feita por nos para saber o numero de cursos na taboa temporal sen validar
 */
function get_number_of_courses() {
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $sql = "SELECT COUNT(code) AS total_number_of_items FROM $course_table WHERE status = ".COURSE_REQUEST_PENDING;
    $res = Database::query($sql);
    $obj = Database::fetch_object($res);
    return $obj->total_number_of_items;
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
    '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.' '.'<a href="?reject_course='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><img src="../img/delete.gif" border="0" style="vertical-align: middle" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'"/></a>'.'  '.'<a href="?request_info='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('cesga_AdminAlertInfo'), ENT_QUOTES))."'".')) return false;"><img src="../img/cesga_question.gif" border="0" style="vertical-align: middle" title="'.get_lang('cesga_AdminPedirInfo').'" alt="'.get_lang('cesga_AdminPedirInfo').'"/></a>&nbsp;'.'  '.'<a href="?accept_course='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('cesga_AdminAlertCrear'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('cesga_AdminValidar').'" alt="'.get_lang('cesga_AdminValidar').'"/></a>&nbsp;';
    */
    $sql_request_info = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE (id = ".$id." AND info = 1 )";
    $res_request_info = Database::query($sql_request_info);

    if (Database::num_rows($res_request_info) > 0) { //Si ya se le ha pedido información, no se muestra esa opción

        return
            '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.' '.'<a href="?reject_course='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><img src="../img/delete.gif" border="0" style="vertical-align: middle" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'"/></a>'.'  '.'<a href="?accept_course='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('cesga_AdminAlertCrear'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('cesga_AdminValidar').'" alt="'.get_lang('cesga_AdminValidar').'"/></a>&nbsp;';

    } else {

        return
            '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.'  '.'<a href="?reject_course='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES))."'".')) return false;"><img src="../img/delete.gif" border="0" style="vertical-align: middle" title="'.get_lang('Delete').'" alt="'.get_lang('Delete').'"/></a>'.'  '.'<a href="?accept_course='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('cesga_AdminAlertCrear'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('cesga_AdminValidar').'" alt="'.get_lang('cesga_AdminValidar').'"/></a>'.'  '.'<a href="?request_info='.$id.'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('cesga_AdminAlertInfo'), ENT_QUOTES))."'".')) return false;"><img src="../img/cesga_question.gif" border="0" style="vertical-align: middle" title="'.get_lang('cesga_AdminPedirInfo').'" alt="'.get_lang('cesga_AdminPedirInfo').'"/></a>&nbsp;&nbsp;';
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
$tool_name = get_lang('cesga_AdminValidCursos'); //nombre que aparece en la barra de navegación
Display :: display_header($tool_name);

//api_display_tool_title($tool_name);
if (isset ($_GET['delete_course'])) {
    CourseManager :: delete_course($_GET['delete_course']);
}

// Create a sortable table with the course data
$table = new SortableTable('courses', 'get_number_of_courses', 'get_course_data', 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);
$table->set_header(1, get_lang('Code'));
$table->set_header(2, get_lang('Title'));
$table->set_header(3, get_lang('Category'));
//$table->set_header(4, get_lang('Teacher'), false);
//$table->set_header(5, get_lang('cesga_AdminFechaSolic'), false);
$table->set_header(4, get_lang('Teacher'));
$table->set_header(5, get_lang('cesga_AdminFechaSolic'));
$table->set_header(6, '', false);
$table->set_column_filter(4,'email_filter');
$table->set_column_filter(6,'modify_filter');
$table->set_form_actions(array('delete_courses' => get_lang('DeleteCourse')), 'course');
$table->display();

/* FOOTER */

Display :: display_footer();
