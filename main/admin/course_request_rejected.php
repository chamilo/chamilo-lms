<?php
/* For licensing terms, see /license.txt */

/**
 * A list containig the rejected course requests
 * @package chamilo.admin
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * @author Bruno Rubio Gayo <brubio@cesga.es>, 2010
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

// Including a configuration file.
require api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';


/**
 * Acceptance and creation of the requested course.
 */
if (isset($_GET['accept_course']) && $_GET['accept_course'] != '') { // TODO: Secure $_GET['accept_course'].
    //Constulta de los datos del curso
    $sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE id LIKE '".$_GET['accept_course']."'"; // TODO: Why "SELECT ... id LIKE x"? Why not "SELECT ... id = x"?
    $result = Database :: query($sql);
    $curso_alta = Database :: fetch_array($result);

    //Creación del curso

    $wanted_code = $curso_alta['code'];
    $tutor_name = $curso_alta['tutor_name'];
    $category_code = $curso_alta['category_code'];
    $title = $curso_alta['title'];
    $course_language = $curso_alta['course_language'];
    $keys = define_course_keys($wanted_code, '', $_configuration['db_prefix']);

    if (sizeof($keys)) {
        $visual_code = $keys['currentCourseCode'];
        $code = $keys['currentCourseId'];
        $db_name = $keys['currentCourseDbName'];
        $directory = $keys['currentCourseRepository'];
        $expiration_date = time() + $firstExpirationDelay;
        prepare_course_repository($directory, $code);
        update_Db_course($db_name);
        $pictures_array = fill_course_repository($directory);
        fill_Db_course($db_name, $directory, $course_language,$pictures_array);
        register_course($code, $visual_code, $directory, $db_name, $tutor_name, $category_code, $title, $course_language, $curso_alta['user_id'], $expiration_date);
    }


    // TODO: Sent the e-mail.



    //Una vez creado el curso, cambiamos su estado en la tabla temporal
    $sql_temp = "UPDATE ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." SET status = 1 WHERE id LIKE '".$_GET['accept_course']."'";

    //$sql_temp = "DELETE FROM ".Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST)." WHERE id LIKE '".$_GET['accept_course']."'";
    $result = api_sql_query($sql_temp);

    unset ($_GET['accept_course']);
}

/**
 * Get the number of courses which will be displayed
 */
function get_number_of_courses() {
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $sql = "SELECT COUNT(code) AS total_number_of_items FROM $course_table WHERE status = ".COURSE_REQUEST_REJECTED;
    $res = Database :: query($sql);
    $obj = Database :: fetch_object($res);
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

                   title AS col1,
                   category_code AS col2,
                   tutor_name AS col3,
                   request_date AS col4,
                   id  AS col5
                   FROM $course_table WHERE status = ".COURSE_REQUEST_REJECTED;

    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database :: query($sql);
    $courses = array();

    while ($course = Database :: fetch_row($res)) {
        $courses[] = $course;
    }

    return $courses;
}

/**
 * Actions in the list: edit.
 */

function modify_filter($id) {
    return
        '<a href="editar_curso.php?id='.$id.'"><img src="../img/edit.gif" border="0" style="vertical-align: middle" title="'.get_lang('Edit').'" alt="'.get_lang('Edit').'"/></a>&nbsp;'.' '.
        '<a href="?accept_course='.$id.'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang('cesga_AdminAlertCrear'), ENT_QUOTES))."'".')) return false;"><img src="../img/right.gif" border="0" style="vertical-align: middle" title="'.get_lang('cesga_AdminValidar').'" alt="'.get_lang('cesga_AdminValidar').'"/></a>';
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('cesga_AdminCursosRexeit'); //nombre que aparece en la barra de navegación
Display :: display_header($tool_name);

//api_display_tool_title($tool_name);
if (isset($_GET['delete_course'])) {
    CourseManager :: delete_course($_GET['delete_course']);
}

// Create a sortable table with the course data
$table = new SortableTable('courses', 'get_number_of_courses', 'get_course_data', 2);
$table->set_additional_parameters($parameters);
//$table->set_header(0, '', false);
$table->set_header(0, get_lang('Code'));
$table->set_header(1, get_lang('Title'));
$table->set_header(2, get_lang('Category'));
//$table->set_header(3, get_lang('Teacher'), false);
//$table->set_header(4, get_lang('cesga_AdminFechaSolic'), false);
$table->set_header(3, get_lang('Teacher'));
$table->set_header(4, get_lang('cesga_AdminFechaSolic'));
$table->set_header(5, '', false);
$table->set_column_filter(5, 'modify_filter');
//$table->set_form_actions(array('delete_courses' => get_lang('DeleteCourse')), 'course');
$table->display();

/* FOOTER */

Display :: display_footer();
