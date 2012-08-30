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

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
if (!isset($_GET['user_id'])) {
    api_not_allowed();
}
$user = api_get_user_info($_GET['user_id']);
$tool_name = $user['complete_name'].(empty($user['official_code'])?'':' ('.$user['official_code'].')');
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
//only allow platform admins to login_as, or session admins only for students (not teachers nor other admins)
$statusname = api_get_status_langvars();
$login_as_icon = '';
if (api_is_platform_admin() || (api_is_session_admin() && $row['6'] == $statusname[STUDENT])) {
        $login_as_icon = '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&amp;user_id='.$user['user_id'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('login_as.gif', get_lang('LoginAs')).'</a>';
}
echo '<div class="actions"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.intval($_GET['user_id']).'" title="'.get_lang('Reporting').'">'.Display::return_icon('statistics.png',get_lang('Reporting'),'',  ICON_SIZE_MEDIUM).'</a>'.$login_as_icon.'</div>';

echo Display::page_header($tool_name);

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

echo Display::page_subheader(get_lang('SessionList'));

$tbl_session_course         = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_course                 = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);

$user_id = $user['user_id'];

$sessions = SessionManager::get_sessions_by_user($user_id);

$personal_course_list = array();
if (count($sessions) > 0) {
    $header[] = array (get_lang('Code'), true);
    $header[] = array (get_lang('Title'), true);
    $header[] = array (get_lang('Status'), true);
    $header[] = array ('', false);

    foreach ($sessions as $session_item) {
        
        $data = array ();
        $personal_course_list = array();
        $id_session = $session_item['session_id'];
                
        foreach ($session_item['courses'] as $my_course) {
            $course_info = api_get_course_info($my_course['code']);
            $row = array ();
            $row[] = $my_course['code'];
            $row[] = $course_info['title'];
            $row[] = $my_course['status'] == STUDENT ? get_lang('Student') : get_lang('Teacher');
            
            $tools = '<a href="course_information.php?code='.$course_info['code'].'&id_session='.$id_session.'">'.Display::return_icon('synthese_view.gif', get_lang('Overview')).'</a>'.
                    '<a href="'.api_get_path(WEB_COURSE_PATH).$course_info['path'].'?id_session='.$id_session.'">'.Display::return_icon('course_home.gif', get_lang('CourseHomepage')).'</a>';

            if ($my_course['status'] == STUDENT) {
                $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$course_info['code'].'&user_id='.$user['user_id'].'">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>';
            }
            $row[] = $tools;
            $data[] = $row;
        }
        echo Display::page_subheader($session_item['session_name']);
        Display :: display_sortable_table($header, $data, array (), array(), array ('user_id' => intval($_GET['user_id'])));
    }
} else {
    echo '<p>'.get_lang('NoSessionsForThisUser').'</p>';
}


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
            $tools .= '<a href="user_information.php?action=unsubscribe&course_code='.$course->code.'&user_id='.$user['user_id'].'">'.Display::return_icon('delete.png', get_lang('Delete')).'</a>';

        }
        $row[] = $tools;
        $data[] = $row;
    }
    echo Display::page_subheader(get_lang('Courses'));
    Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => intval($_GET['user_id'])));    
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
    $url_list= UrlManager::get_access_url_from_user($user['user_id']);
    if (count($url_list) > 0) {
        $header = array();
        $header[] = array ('URL', true);
        $data = array ();
        foreach ($url_list as $url) {
            $row = array();
            $row[] = Display::url($url['url'], $url['url']);
            $data[] = $row;
        }
        echo '<p><b>'.get_lang('URLList').'</b></p>';        
        Display :: display_sortable_table($header, $data, array (), array (), array ('user_id' => intval($_GET['user_id'])));        
    } else {
        echo '<p>'.get_lang('NoUrlForThisUser').'</p>';
    }
}

/* FOOTER */
Display::display_footer();