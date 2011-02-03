<?php
/* For licensing terms, see /license.txt */
/**
 *    @author Bart Mollet
 *    @package chamilo.admin
 */
/* INIT SECTION */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
if ( ! isset($_GET['user_id'])) {
    api_not_allowed();
}
$user = api_get_user_info($_GET['user_id']);
$tool_name = api_get_person_name($user['firstName'], $user['lastName']).(empty($user['official_code'])?'':' ('.$user['official_code'].')');
Display::display_header($tool_name);
$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
if ( isset($_GET['action']) ) {
    switch($_GET['action']) {
        case 'unsubscribe':
            if ( CourseManager::get_user_in_course_status($_GET['user_id'],$_GET['course_code']) == STUDENT) {
                CourseManager::unsubscribe_user($_GET['user_id'],$_GET['course_code']);
                Display::display_normal_message(get_lang('UserUnsubscribed'));
            } else {
                Display::display_error_message(get_lang('CannotUnsubscribeUserFromCourse'));
            }
            break;
    }
}
api_display_tool_title($tool_name);
//only allow platform admins to login_as, or session admins only for students (not teachers nor other admins)
$statusname = api_get_status_langvars();
$login_as_icon = '';
if (api_is_platform_admin() || (api_is_session_admin() && $row['6'] == $statusname[STUDENT])) {
        $login_as_icon = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&amp;user_id='.$user['user_id'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('login_as.gif', get_lang('LoginAs')).'</a>';
}
echo '<div align="right" style="margin-right:4em;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.intval($_GET['user_id']).'" title="'.get_lang('Reporting').'">'.Display::return_icon('statistics.gif',get_lang('Reporting')).'</a>'.$login_as_icon.'</div>'."\n";
//getting the user image
$sysdir_array = UserManager::get_user_picture_path_by_id($user['user_id'],'system',false,true);
$sysdir = $sysdir_array['dir'];
$webdir_array = UserManager::get_user_picture_path_by_id($user['user_id'],'web',false,true);
$webdir = $webdir_array['dir'];
$fullurl=$webdir.$webdir_array['file'];
$system_image_path=$sysdir.$webdir_array['file'];
list($width, $height, $type, $attr) = @getimagesize($system_image_path);
$resizing = (($height > 200) ? 'height="200"' : '');
$height += 30;
$width += 30;
$window_name = 'window'.uniqid('');
$onclick = $window_name."=window.open('".$fullurl."','".$window_name."','alwaysRaised=yes, alwaysLowered=no,alwaysOnTop=yes,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=no,width=".$width.",height=".$height.",left=200,top=20'); return false;";
echo '<a href="javascript: void(0);" onclick="'.$onclick.'" ><img src="'.$fullurl.'" '.$resizing.' alt="'.$alt.'"/></a><br />';
echo '<p>'. ($user['status'] == 1 ? get_lang('Teacher') : get_lang('Student')).'</p>';
echo '<p>'.Display :: encrypted_mailto_link($user['mail'], $user['mail']).'</p>';

/**
 * Show the sessions and the courses in wich this user is subscribed
 */

echo '<p><b>'.get_lang('SessionList').'</b></p>';
echo '<blockquote>';

$main_user_table            = Database :: get_main_table(TABLE_MAIN_USER);
$main_course_table          = Database :: get_main_table(TABLE_MAIN_COURSE);
$main_course_user_table     = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session_course         = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);

$user_id = $user['user_id'];

$result = Database::query("SELECT DISTINCT id, name, date_start, date_end ".
    " FROM session_rel_user, session ".
    " WHERE id_session=id AND id_user=$user_id ".
    " AND (date_start <= NOW() AND date_end >= NOW() OR date_start='0000-00-00') ".
    " ORDER BY date_start, date_end, name");

$sessions = Database::store_result($result);

/*
// Get the list of sessions where the user is subscribed as coach in a course
$sql = "SELECT DISTINCT id, name, date_start, date_end FROM $tbl_session as session ".
    " INNER JOIN $tbl_session_course_user as session_rel_course_rel_user ".
    " ON session_rel_course_rel_user.id_user = $user_id AND status = 2 ".
    " AND (date_start <= NOW() AND date_end >= NOW() OR date_start='0000-00-00') ".
    " ORDER BY date_start, date_end, name";

$result = Database::query($sql);
$session_is_coach = Database::store_result($result);
*/

$personal_course_list = array();
if (count($sessions)>0) {
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array (get_lang('Status'), true);
    $header[] = array ('', false);

    foreach ($sessions as $enreg) {

        $data = array ();
        $personal_course_list = array();

        $id_session = $enreg['id'];
        $personal_course_list_sql = "SELECT distinct course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, ".(api_is_western_name_order() ? "CONCAT(user.firstname,' ',user.lastname)" : "CONCAT(user.lastname,' ',user.firstname)")." t, email, " .
                "course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name, IF((session_course_user.id_user = 3 AND session_course_user.status=2),'2', '5') ".
            " FROM $tbl_session_course_user as session_course_user INNER JOIN $tbl_course AS course ".
            " ON course.code = session_course_user.course_code AND session_course_user.id_session = $id_session ".
            " INNER JOIN $tbl_session as session ON session_course_user.id_session = session.id ".
            " INNER JOIN $tbl_session_course as session_course ".
            " LEFT JOIN $tbl_user as user ON user.user_id = session_course_user.id_user AND session_course_user.status = 2 ".
            " WHERE session_course_user.id_user = $user_id  ORDER BY i";
        $course_list_sql_result = Database::query($personal_course_list_sql);

        while ($result_row = Database::fetch_array($course_list_sql_result)) {
            $key = $result_row['id_session'].' - '.$result_row['k'];
            $result_row['s'] = $result_row['14'];

            if (!isset($personal_course_list[$key])) {
                $personal_course_list[$key] = $result_row;
            }
        }
        foreach ($personal_course_list as $my_course) {
            $row = array ();
            $row[] = $my_course['k'];
            $row[] = $my_course['i'];
            $row[] = $my_course['s'] == STUDENT ? get_lang('Student') : get_lang('Teacher');
            $tools = '<a href="course_information.php?code='.$my_course['k'].'&id_session='.$id_session.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                    '<a href="'.api_get_path(WEB_COURSE_PATH).$my_course['d'].'?id_session='.$id_session.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>' .
                    '<a href="session_course_edit.php?id_session='.$id_session.'&course_code='.$my_course['k'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';

            if( $my_course->status == STUDENT ){
                $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$my_course['k'].'&user_id='.$user['user_id'].'">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';

            }
            $row[] = $tools;
            $data[] = $row;
        }
        echo $enreg['name'];
        Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => intval($_GET['user_id'])));
        echo '<br><br><br>';

    }
} else {
    echo '<p>'.get_lang('NoSessionsForThisUser').'</p>';
}

echo '</blockquote>';

/**
 * Show the courses in which this user is subscribed
 */
$sql = 'SELECT * FROM '.$table_course_user.' cu, '.$table_course.' c'.
    ' WHERE cu.user_id = '.$user['user_id'].' AND cu.course_code = c.code '.
    ' AND cu.relation_type <> '.COURSE_RELATION_TYPE_RRHH.' ';
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    $header=array();
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array (get_lang('Status'), true);
    $header[] = array ('', false);
    $data = array ();
    while ($course = Database::fetch_object($res)) {
        $row = array ();
        $row[] = $course->code;
        $row[] = $course->title;
        $row[] = $course->status == STUDENT ? get_lang('Student') : get_lang('Teacher');
        $tools = '<a href="course_information.php?code='.$course->code.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                '<a href="'.api_get_path(WEB_COURSE_PATH).$course->directory.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>' .
                '<a href="course_edit.php?course_code='.$course->code.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
        if ( $course->status == STUDENT ) {
            $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$course->code.'&user_id='.$user['user_id'].'">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';

        }
        $row[] = $tools;
        $data[] = $row;
    }

    echo '<p><b>'.get_lang('Courses').'</b></p>';
    echo '<blockquote>';
    Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => intval($_GET['user_id'])));
    echo '</blockquote>';
} else {
    echo '<p>'.get_lang('NoCoursesForThisUser').'</p>';
}

/**
 * Show the classes in which this user is subscribed
 */
$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
$sql = 'SELECT * FROM '.$table_class_user.' cu, '.$table_class.' c '.
    ' WHERE cu.user_id = '.$user['user_id'].' AND cu.class_id = c.id';
$res = Database::query($sql);
if (Database::num_rows($res) > 0) {
    $header = array();
    $header[] = array (get_lang('ClassName'), true);
    $header[] = array ('', false);
    $data = array ();
    while ($class = Database::fetch_object($res)) {
        $row = array();
        $row[] = $class->name;
        $row[] = '<a href="class_information.php?id='.$class->id.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>';
        $data[] = $row;
    }
    echo '<p><b>'.get_lang('Classes').'</b></p>';
    echo '<blockquote>';
    Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => intval($_GET['user_id'])));
    echo '</blockquote>';
} else {
    echo '<p>'.get_lang('NoClassesForThisUser').'</p>';
}

/**
 * Show the URL in which this user is subscribed
 */
global $_configuration;
if ($_configuration['multiple_access_urls']) {
    require_once(api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
    $url_list= UrlManager::get_access_url_from_user($user['user_id']);
    if (count($url_list) > 0) {
        $header = array();
        $header[] = array (get_lang('URL'), true);
        $data = array ();
        foreach ($url_list as $url) {
            $row = array();
            $row[] = $url['url'];
            $data[] = $row;
        }
        echo '<p><b>'.get_lang('URLList').'</b></p>';
        echo '<blockquote>';
        Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => intval($_GET['user_id'])));
        echo '</blockquote>';
    } else {
        echo '<p>'.get_lang('NoUrlForThisUser').'</p>';
    }
}
/* FOOTER */
Display::display_footer();