<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Refactoring
 * @todo check the different @todos in this page and really do them
 * @todo check if the news management works as expected
 */

// Only this script should have this constant defined. This is used to activate the javascript that
// gives the login name automatic focus in header.inc.html.
/** @todo Couldn't this be done using the $HtmlHeadXtra array? */
define('CHAMILO_HOMEPAGE', true);

$language_file = array('courses', 'index');

/* Flag forcing the 'current course' reset, as we're not inside a course anymore. */
// Maybe we should change this into an api function? an example: Coursemanager::unset();
$cidReset = true;

/* Included libraries */

/** @todo Make all the library files consistent, use filename.lib.php and not filename.lib.inc.php. */
require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'events.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once 'main/chat/chat_functions.lib.php';


$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);
$setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

// The section (for the tabs).
$this_section = SECTION_CAMPUS;
unset($_SESSION['this_section']);//for hmtl editor repository

/* Action Handling */

/** @todo   Wouldn't it make more sense if this would be done in local.inc.php so that local.inc.php become the only place where authentication is done?
 *          by doing this you could logout from any page instead of only from index.php. From the moment there is a logout=true in the url you will be logged out
 *          this can be usefull when you are on an open course and you need to log in to edit something and you immediately want to check how anonymous users
 *          will see it.
 */
$my_user_id = api_get_user_id();

if (!empty($_GET['logout'])) {
    logout();
}

/* Table definitions */
$main_course_table      = Database :: get_main_table(TABLE_MAIN_COURSE);
$main_category_table    = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$track_login_table      = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

/* Constants and CONFIGURATION parameters */
/** @todo these configuration settings should move to the Chamilo config settings. */

/** Defines wether or not anonymous visitors can see a list of the courses on the Chamilo homepage that are open to the world. */
$_setting['display_courses_to_anonymous_users'] = 'true';

/** @todo Remove this piece of code because this is not used. */
if (isset($_user['user_id'])) {
    $nameTools = api_get_setting('siteName');
}

/* LOGIN */

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login / logout procedure is handled.
 * @todo Consider removing this piece of code because does nothing.
 */
if (isset($_GET['submitAuth']) && $_GET['submitAuth'] == 1) {
    // nice lie!!!
    echo 'Attempted breakin - sysadmins notified.';
    session_destroy();
    die();
}

// Delete session neccesary for legal terms
if (api_get_setting('allow_terms_conditions') == 'true') {
    unset($_SESSION['update_term_and_condition']);
    unset($_SESSION['info_current_user']);
}
//If we are not logged in and customapages activated
if (!api_get_user_id() && api_get_setting('use_custom_pages') == 'true' ){
  require_once api_get_path(LIBRARY_PATH).'custompages.lib.php';
  CustomPages::displayPage('index-unlogged');
}


/**
 * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
 * @todo Check if this code is used. I think this code is never executed because after clicking the submit button
 *       the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
 *       on api_get_setting('page_after_login').
 */

if (!empty($_POST['submitAuth'])) {
    // The user has been already authenticated, we are now to find the last login of the user.
    if (isset ($_user['user_id'])) {
        $sql_last_login = "SELECT UNIX_TIMESTAMP(login_date)
                                FROM $track_login_table
                                WHERE login_user_id = '".$_user['user_id']."'
                                ORDER BY login_date DESC LIMIT 1";
        $result_last_login = Database::query($sql_last_login);
        if (!$result_last_login) {
            if (Database::num_rows($result_last_login) > 0) {
                $user_last_login_datetime = Database::fetch_array($result_last_login);
                $user_last_login_datetime = $user_last_login_datetime[0];
                api_session_register('user_last_login_datetime');
            }
        }
        Database::free_result($result_last_login);

        //event_login();
        if (api_is_platform_admin()) {
            // decode all open event informations and fill the track_c_* tables
            include api_get_path(LIBRARY_PATH).'stats.lib.inc.php';
            decodeOpenInfos();
        }
    }

} // End login -- if ($_POST['submitAuth'])
else {
    // Only if login form was not sent because if the form is sent the user was already on the page.
    event_open();
}
// The header.
/*$header_title = get_lang('Homepage');
//$sitename = api_get_setting('siteName');
if (!api_get_user_id()) { 
    $header_title = null;
}*/
$header_title = null;
if (!api_is_anonymous()) {
    $header_title = " ";
}

Display::display_header($header_title);

/* MAIN CODE */

echo '<div class="maincontent" id="content">';

// Plugins for loginpage_main AND campushomepage_main.
if (!api_get_user_id()) {
    api_plugin('loginpage_main');
} else {
    api_plugin('campushomepage_main');
}

$home = 'home/';
if ($_configuration['multiple_access_urls']) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1){
        $url_info = api_get_access_url($access_url_id);
        $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
        $clean_url = replace_dangerous_char($url);
        $clean_url = str_replace('/', '-', $clean_url);
        $clean_url .= '/';
        $home_old = 'home/';
        $home = 'home/'.$clean_url;
    }
}

// Including the page for the news
$page_included = false;

if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
    $open = @(string)file_get_contents(api_get_path(SYS_PATH).$home.$_GET['include']);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    echo $open;
    $page_included = true;
} else {

    if (!empty($_SESSION['user_language_choice'])) {
        $user_selected_language = $_SESSION['user_language_choice'];
    } elseif (!empty($_SESSION['_user']['language'])) {
        $user_selected_language = $_SESSION['_user']['language'];
    } else {
        $user_selected_language = api_get_setting('platformLanguage');
    }

    if (!file_exists($home.'home_news_'.$user_selected_language.'.html')) {
        if (file_exists($home.'home_top.html')) {
            $home_top_temp = file($home.'home_top.html');
        } else {
            $home_top_temp = file($home_old.'home_top.html');
        }
        $home_top_temp = implode('', $home_top_temp);
    } else {
        if (file_exists($home.'home_top_'.$user_selected_language.'.html')) {
            $home_top_temp = file_get_contents($home.'home_top_'.$user_selected_language.'.html');
        } else {
            $home_top_temp = file_get_contents($home.'home_top.html');
        }
    }
    if (trim($home_top_temp) == '' && api_is_platform_admin()) {
        $home_top_temp = get_lang('PortalHomepageDefaultIntroduction');
    }
    $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    echo $open;
}

// Display courses and category list.
if (!$page_included) {

    // Display System announcements
    $announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
    $announcement = intval($announcement);

    if (isset($_user['user_id'])) {
        $visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
        SystemAnnouncementManager :: display_announcements($visibility, $announcement);
    } else {
        SystemAnnouncementManager :: display_announcements(VISIBLE_GUEST, $announcement);
    }

    if (api_get_setting('display_categories_on_homepage') == 'true') {
        echo '<div class="home_cats">';
        display_anonymous_course_list();
        echo '</div>';
    }
}
echo '</div>';


echo '<div id="menu-wrapper">';
if (!api_is_anonymous()) {
    //  @todo move all this in a class/function    
        
    //Always show the user image
    $img_array = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'web', true, true);
    $no_image = false;
    if ($img_array['file'] == 'unknown.jpg') {
        $no_image = true;
    }
    $img_array = UserManager::get_picture_user(api_get_user_id(), $img_array['file'], 50, USER_IMAGE_SIZE_MEDIUM, ' width="90" height="90" ');
    
    $profile_content .='<div id="social_widget">';
    
    $profile_content .= '<div id="social_widget_image">';
    if (api_get_setting('allow_social_tool') == 'true') {
        if (!$no_image) {
            $profile_content .='<a href="'.api_get_path(WEB_PATH).'main/social/home.php"><img src="'.$img_array['file'].'"  '.$img_array['style'].' border="1"></a>';
        } else {
            $profile_content .='<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
        }
    } else {
        $profile_content .='<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
    }
    $profile_content .= ' </div></div>';
    
    if (api_get_setting('allow_message_tool') == 'true') {
    
        require_once api_get_path(LIBRARY_PATH).'message.lib.php';
        require_once api_get_path(LIBRARY_PATH).'social.lib.php';
        require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
    
        // New messages.
        $number_of_new_messages             = MessageManager::get_new_messages();
        // New contact invitations.
        $number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
    
        // New group invitations sent by a moderator.
        $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION, false);
        $group_pending_invitations = count($group_pending_invitations);
    
        $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
        $cant_msg  = '';
        if ($number_of_new_messages > 0) {
            $cant_msg = ' ('.$number_of_new_messages.')';
        }
        $profile_content .= '<div class="clear"></div>';
        $profile_content .= '<div class="message-content"><ul class="menulist">';
        $link = '';
        if (api_get_setting('allow_social_tool') == 'true') {
            $link = '?f=social';
        }
        $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$link.'" class="message-body">'.get_lang('Inbox').$cant_msg.' </a></li>';
        $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$link.'" class="message-body">'.get_lang('Compose').' </a></li>';
        
        if (api_get_setting('allow_social_tool') == 'true') {        
            if ($total_invitations == 0) {
                $total_invitations = '';
            } else {
               $total_invitations = ' ('.$total_invitations.')';
            }
            $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php" class="message-body">'.get_lang('PendingInvitations').' '.$total_invitations.' </a></li>';
        }
        $profile_content .= '</ul>';
        $profile_content .= '</div>';      
    }
    
    //Profile content
    echo show_right_block(get_lang('Profile'), $profile_content);
}

// Display right menu: language form, login section + useful weblinks.

display_anonymous_right_menu();

echo '</div>';

/* Footer */

Display :: display_footer();

/* Functions */

/**
 * This function handles the logout and is called whenever there is a $_GET['logout']
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function logout() {
    global $_configuration, $extAuthSource;
    // Variable initialisation.
    $query_string = '';

    if (!empty($_SESSION['user_language_choice'])) {
        $query_string = '?language='.$_SESSION['user_language_choice'];
    }

    // Database table definition.
    $tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

    // Selecting the last login of the user.
    $uid = intval($_GET['uid']);
    $sql_last_connection = "SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='$uid' ORDER BY login_date DESC LIMIT 0,1";
    $q_last_connection = Database::query($sql_last_connection);
    if (Database::num_rows($q_last_connection) > 0) {
        $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');
    }

    if (!isset($_SESSION['login_as'])) {
        $current_date = date('Y-m-d H:i:s', time());
        $s_sql_update_logout_date = "UPDATE $tbl_track_login SET logout_date='".$current_date."' WHERE login_id='$i_id_last_connection'";
        Database::query($s_sql_update_logout_date);
    }
    LoginDelete($uid); // From inc/lib/online.inc.php - removes the "online" status.

    // The following code enables the use of an external logout function.
    // Example: define a $extAuthSource['ldap']['logout'] = 'file.php' in configuration.php.
    // Then a function called ldap_logout() inside that file
    // (using *authent_name*_logout as the function name) and the following code
    // will find and execute it.
    $uinfo = api_get_user_info($uid);
    if (($uinfo['auth_source'] != PLATFORM_AUTH_SOURCE) && is_array($extAuthSource)) {
        if (is_array($extAuthSource[$uinfo['auth_source']])) {
            $subarray = $extAuthSource[$uinfo['auth_source']];
            if (!empty($subarray['logout']) && file_exists($subarray['logout'])) {
                include_once ($subarray['logout']);
                $logout_function = $uinfo['auth_source'].'_logout';
                if (function_exists($logout_function)) {
                    $logout_function($uinfo);
                }
            }
        }
    }
    exit_of_chat($uid);
    api_session_destroy();
    header("Location: index.php$query_string");
    exit();
}

/**
 * This function checks if there are courses that are open to the world in the platform course categories (=faculties)
 *
 * @param unknown_type $category
 * @return boolean
 */
function category_has_open_courses($category) {
    global $setting_show_also_closed_courses;

    $user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
    $main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
    $sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
    $sql_result = Database::query($sql_query);
    while ($course = Database::fetch_array($sql_result)) {
        if (!$setting_show_also_closed_courses) {
            if ((api_get_user_id() > 0
                && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
                return true; //at least one open course
            }
        } else {
            if (isset($course['visibility'])) {
                return true; // At least one course (it does not matter weither it's open or not because $setting_show_also_closed_courses = true).
            }
        }
    }
    return false;
}

/**
 * Display create course link
 */
function display_create_course_link() {
    echo '<li><a href="main/create_course/add_course.php">'.
        (api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')).'</a></li>';
}

/**
 * Display edit course list links
 */
function display_edit_course_list_links() {
    echo '<li><a href="main/auth/courses.php">'.get_lang('CourseManagement').'</a></li>';
}

/**
 * Display dashboard link
 */
function display_dashboard_link() {
    echo '<li><a href="main/dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
}

/**
 * Displays the right-hand menu for anonymous users:
 * login form, useful links, help section
 * Warning: function defines globals
 * @version 1.0.1
 * @todo does $_plugins need to be global?
 */
function display_anonymous_right_menu() {
    global $loginFailed, $_plugins, $_user, $menu_navigation, $home, $home_old;

    $platformLanguage       = api_get_setting('platformLanguage');
    $sys_path               = api_get_path(SYS_PATH);
    $user_selected_language = api_get_interface_language();
    
    echo '<div class="menu" id="menu">';

    if (!($_user['user_id']) || api_is_anonymous($_user['user_id']) ) { // Only display if the user isn't logged in.
        api_display_language_form(true);
        echo '<br />';
        display_login_form();

        if ($loginFailed) {
            echo '<br />';
            handle_login_failed();
        }
        if (api_get_setting('allow_lostpassword') == 'true' || api_get_setting('allow_registration') == 'true') {
            echo '<div class="menusection"><span class="menusectioncaption">'.get_lang('MenuUser').'</span><ul class="menulist">';
            if (api_get_setting('allow_registration') != 'false') {
                echo '<li><a href="main/auth/inscription.php">'.get_lang('Reg').'</a></li>';
            }
            if (api_get_setting('allow_lostpassword') == 'true') {
                display_lost_password_info();
            }
            echo '</ul></div>';
        }
        if (api_number_of_plugins('loginpage_menu') > 0) {
            echo '<div class="note" style="background: none">';
            api_plugin('loginpage_menu');
            echo '</div>';
        }
    }

    // My Account section.
    if (isset($_SESSION['_user']['user_id']) && $_SESSION['_user']['user_id'] != 0) {
        // tabs that are deactivated are added here

        $show_menu = false;
        $show_create_link = false;
        $show_course_link = false;

        $display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION['studentview'] != 'studentenview');

        if ($display_add_course_link) {
            //display_create_course_link();
            $show_menu = true;
            $show_create_link = true;
        }

        if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
            $show_menu = true;
            $show_course_link = true;
        } else {
            if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                $show_menu = true;
                $show_course_link = true;
            }
        }

        if ($show_menu) {
            echo '<div class="menusection">';
            echo '<span class="menusectioncaption">'.get_lang('MenuUser').'</span>';
            echo '<ul class="menulist">';
            if ($show_create_link) {
                display_create_course_link();
            }
            if ($show_course_link) {
                if (!api_is_drh() && !api_is_session_admin()) {
                    display_edit_course_list_links();
                } else {
                    display_dashboard_link();
                }
            }
            echo '</ul></div>';
        }
    }    
    echo '</div>';
    
    // Notice

    $home_notice = @(string)file_get_contents($sys_path.$home.'home_notice_'.$user_selected_language.'.html');
    if (empty($home_notice)) {
        $home_notice = @(string)file_get_contents($sys_path.$home.'home_notice.html');
    }
    
    if (!empty($home_notice)) {        
        $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));        
        echo show_right_block('', $home_notice, 'note');
    }
    
    
    
    if (isset($_SESSION['_user']['user_id']) && $_SESSION['_user']['user_id'] != 0) {
            // Deleting the myprofile link.
        if (api_get_setting('allow_social_tool') == 'true') {
            unset($menu_navigation['myprofile']);
        }

        if (!empty($menu_navigation)) {            
            $content = '<ul class="menulist">';
            foreach ($menu_navigation as $section => $navigation_info) {
                $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
                $content .='<li'.$current.'><a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a></li>';
            }
            $content .= '</ul>';
            echo show_right_block(get_lang('MainNavigation'), $content);
        }
    }      

    // Help section.
    /* Hide right menu "general" and other parts on anonymous right menu. */
    
    if (!isset($user_selected_language)) {
        $user_selected_language = $platformLanguage;
    }
    
    $home_menu = @(string)file_get_contents($sys_path.$home.'home_menu_'.$user_selected_language.'.html');    
    if (!empty($home_menu)) {        
        $home_menu_content .= '<ul class="menulist">';
        $home_menu_content .= api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));         
        $home_menu_content .= '</ul>';        
        echo show_right_block(get_lang('MenuGeneral'), $home_menu_content);
    }
    //Plugin 
    
    if ($_user['user_id'] && api_number_of_plugins('campushomepage_menu') > 0) {
        ob_start();
        api_plugin('campushomepage_menu');
        $plugin_content = ob_get_contents();
        ob_end_clean();
        echo show_right_block('', $plugin_content);
    }
}

/**
 * Reacts on a failed login:
 * Displays an explanation with a link to the registration form.
 *
 * @version 1.0.1
 */
function handle_login_failed() {
    if (!isset($_GET['error'])) {
        $message = get_lang('InvalidId');
        if (api_is_self_registration_allowed()) {
            $message = get_lang('InvalidForSelfRegistration');
        }
    } else {
        switch ($_GET['error']) {
            case '':
                $message = get_lang('InvalidId');
                if (api_is_self_registration_allowed()) {
                    $message = get_lang('InvalidForSelfRegistration');
                }
                break;
            case 'account_expired':
                $message = get_lang('AccountExpired');
                break;
            case 'account_inactive':
                $message = get_lang('AccountInactive');
                break;
            case 'user_password_incorrect':
                $message = get_lang('InvalidId');
                break;
            case 'access_url_inactive':
                $message = get_lang('AccountURLInactive');
                break;
        }
    }
    echo '<div id="login_fail">'.$message.'</div>';
}

/**
 * Adds a form to let users login
 * @version 1.1
 */
function display_login_form() {
    $form = new FormValidator('formLogin');
    $form->addElement('text', 'login', get_lang('UserName'), array('size' => 17));
    $form->addElement('password', 'password', get_lang('Pass'), array('size' => 17));
    $form->addElement('style_submit_button','submitAuth', get_lang('LoginEnter'), array('class' => 'login'));
    $renderer =& $form->defaultRenderer();
    $renderer->setElementTemplate('<div><label>{label}</label></div><div>{element}</div>');
    $form->display();
    if (api_get_setting('openid_authentication') == 'true') {
        include_once 'main/auth/openid/login.php';
        echo '<div>'.openid_form().'</div>';
    }
}

/**
 * Displays a link to the lost password section
 */
function display_lost_password_info() {
    echo '<li><a href="main/auth/lostPassword.php">'.get_lang('LostPassword').'</a></li>';
}

/**
* Display list of courses in a category.
* (for anonymous users)
*
* @version 1.1
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
*/
function display_anonymous_course_list() {
    $ctok = $_SESSION['sec_token'];
    $stok = Security::get_token();

    // Initialization.
    $user_identified = (api_get_user_id() > 0 && !api_is_anonymous());
    $web_course_path = api_get_path(WEB_COURSE_PATH);
    $category = Database::escape_string($_GET['category']);
    global $setting_show_also_closed_courses;

    // Database table definitions.
    $main_course_table      = Database :: get_main_table(TABLE_MAIN_COURSE);
    $main_category_table    = Database :: get_main_table(TABLE_MAIN_CATEGORY);

    $platformLanguage = api_get_setting('platformLanguage');

    // Get list of courses in category $category.
    $sql_get_course_list = "SELECT * FROM $main_course_table cours
                                WHERE category_code = '".Database::escape_string($_GET['category'])."'
                                ORDER BY title, UPPER(visual_code)";

    // Showing only the courses of the current access_url_id.
    global $_configuration;
    if ($_configuration['multiple_access_urls']) {
        $url_access_id = api_get_current_access_url_id();
        if ($url_access_id != -1) {
            $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $sql_get_course_list = "SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.course_code=course.code)
                    WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string($_GET['category'])."' ORDER BY title, UPPER(visual_code)";
        }
    }

    // Removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
    $sql_result_courses = Database::query($sql_get_course_list);

    while ($course_result = Database::fetch_array($sql_result_courses)) {
        $course_list[] = $course_result;
    }

    $platform_visible_courses = '';
    // $setting_show_also_closed_courses
    if ($user_identified) {
        if ($setting_show_also_closed_courses) {
            $platform_visible_courses = '';
        } else {
            $platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' OR t3.visibility='".COURSE_VISIBILITY_OPEN_PLATFORM."' )";
        }
    } else {
        if ($setting_show_also_closed_courses) {
            $platform_visible_courses = '';
        } else {
            $platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' )";
        }
    }
    $sqlGetSubCatList = "
                SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                FROM $main_category_table t1
                LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                WHERE t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."
                GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";


    // Showing only the category of courses of the current access_url_id.
    global $_configuration;
    if ($_configuration['multiple_access_urls']) {
        $url_access_id = api_get_current_access_url_id();
        if ($url_access_id != -1) {
            $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $sqlGetSubCatList = "
                SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                FROM $main_category_table t1
                LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                INNER JOIN $tbl_url_rel_course as url_rel_course
                    ON (url_rel_course.course_code=t3.code)
                WHERE access_url_id = $url_access_id AND t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
        }
    }

    $resCats = Database::query($sqlGetSubCatList);
    $thereIsSubCat = false;
    if (Database::num_rows($resCats) > 0) {
        $htmlListCat = '<h4 style="margin-top: 0px;">'.get_lang('CatList').'</h4><ul>';
        while ($catLine = Database::fetch_array($resCats)) {
            if ($catLine['code'] != $category) {

                $category_has_open_courses = category_has_open_courses($catLine['code']);
                if ($category_has_open_courses) {
                    // The category contains courses accessible to anonymous visitors.
                    $htmlListCat .= '<li>';
                    $htmlListCat .= '<a href="'.api_get_self().'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                    if (api_get_setting('show_number_of_courses') == 'true') {
                        $htmlListCat .= ' ('.$catLine['nbCourse'].' '.get_lang('Courses').')';
                    }
                    $htmlListCat .= "</li>\n";
                    $thereIsSubCat = true;
                } elseif ($catLine['children_count'] > 0) {
                    // The category has children, subcategories.
                    $htmlListCat .= '<li>';
                    $htmlListCat .= '<a href="'.api_get_self().'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                    $htmlListCat .= "</li>\n";
                    $thereIsSubCat = true;
                }
                /* End changed code to eliminate the (0 courses) after empty categories. */
                elseif (api_get_setting('show_empty_course_categories') == 'true') {
                    $htmlListCat .= '<li>';
                    $htmlListCat .= $catLine['name'];
                    $htmlListCat .= "</li>\n";
                    $thereIsSubCat = true;
                } // Else don't set thereIsSubCat to true to avoid printing things if not requested.
            } else {
                $htmlTitre = '<p>';
                if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
                    $htmlTitre .= '<a href="'.api_get_self().'">&lt;&lt; '.get_lang('BackToHomePage').'</a>';
                }
                if (!is_null($catLine['parent_id']) || (api_get_setting('show_back_link_on_top_of_tree') != 'true' && !is_null($catLine['code']))) {
                    $htmlTitre .= '<a href="'.api_get_self().'?category='.$catLine['parent_id'].'">&lt;&lt; '.get_lang('Up').'</a>';
                }
                $htmlTitre .= "</p>\n";
                if ($category != "" && !is_null($catLine['code'])) {
                    $htmlTitre .= '<h3>'.$catLine['name']."</h3>\n";
                } else {
                    $htmlTitre .= '<h3>'.get_lang('Categories')."</h3>\n";
                }
            }
        }
        $htmlListCat .= "</ul>\n";
    }
    echo $htmlTitre;
    if ($thereIsSubCat) {
        echo $htmlListCat;
    }
    while ($categoryName = Database::fetch_array($resCats)) {
        echo '<h3>', $categoryName['name'], "</h3>\n";
    }
    $numrows = Database::num_rows($sql_result_courses);
    $courses_list_string = '';
    $courses_shown = 0;
    if ($numrows > 0) {
        if ($thereIsSubCat) {
            $courses_list_string .= "<hr size=\"1\" noshade=\"noshade\">\n";
        }
        $courses_list_string .= '<h4 style="margin-top: 0px;">'.get_lang('CourseList')."</h4>\n<ul>\n";

        if (api_get_user_id()) {
            $courses_of_user = get_courses_of_user(api_get_user_id());
        }

        foreach ($course_list as $course) {
            // $setting_show_also_closed_courses
            if (!$setting_show_also_closed_courses) {
                // If we do not show the closed courses
                // we only show the courses that are open to the world (to everybody)
                // and the courses that are open to the platform (if the current user is a registered user.
                if( ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
                    $courses_shown++;
                    $courses_list_string .= "<li>\n";
                    $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">'.$course['title'].'</a><br />';
                    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                        $courses_list_string .= $course['visual_code'];
                    }
                    if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
                        $courses_list_string .= ' - ';
                    }
                    if (api_get_setting('display_teacher_in_courselist') == 'true') {
                        $courses_list_string .= $course['tutor_name'];
                    }
                    if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                        $courses_list_string .= ' - '.$course['course_language'];
                    }
                    $courses_list_string .= "</li>\n";
                }
            }
            // We DO show the closed courses.
            // The course is accessible if (link to the course homepage):
            // 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD);
            // 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM);
            // 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED;
            // 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting);
            // 5. the user is the platform admin api_is_platform_admin().
            //
            else {
                $courses_shown++;
                $courses_list_string .= "<li>\n";
                if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()) {
                    $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                }
                $courses_list_string .= $course['title'];
                if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists($course['code'], $courses_of_user) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()) {
                    $courses_list_string .= '</a><br />';
                }
                if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                    $courses_list_string .= ' '.$course['visual_code'];
                }
                if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
                    $courses_list_string .= ' - ';
                }
                if (api_get_setting('display_teacher_in_courselist') == 'true') {
                    $courses_list_string .= $course['tutor_name'];
                }
                if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                    $courses_list_string .= ' - '.$course['course_language'];
                }
                if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] != api_get_setting('platformLanguage')) {
                    $courses_list_string .= ' - '.$course['course_language'];
                }
                // We display a subscription link if:
                // 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
                // 2.
                if ($user_identified && !key_exists($course['code'], $courses_of_user)) {
                    if ($course['subscribe'] == '1') {
                        $courses_list_string .= '<form action="main/auth/courses.php?action=subscribe&category='.Security::remove_XSS($_GET['category']).'" method="post">';
                        $courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
                        $courses_list_string .= '<input type="hidden" name="subscribe" value="'.$course['code'].'" />';
                        $courses_list_string .= '<input type="image" name="unsub" src="main/img/enroll.gif" alt="'.get_lang('Subscribe').'" />'.get_lang('Subscribe').'</form>';
                    } else {
                        $courses_list_string .= '<br />'.get_lang('SubscribingNotAllowed');
                    }
                }
                $courses_list_string .= "</li>\n";
            }
        }
        $courses_list_string .= "</ul>\n";
    } else {
        //echo '<blockquote>', get_lang('_No_course_publicly_available'), "</blockquote>\n";
    }
    if ($courses_shown > 0) {   // Only display the list of courses and categories if there was more than
                                // 0 courses visible to the world (we're in the anonymous list here).
        echo $courses_list_string;
    }
    if ($category != '') {
        echo '<p><a href="'.api_get_self().'"> ', Display :: return_icon('back.png', get_lang('BackToHomePage')), get_lang('BackToHomePage'), '</a></p>', "\n";
    }
}

/**
 * retrieves all the courses that the user has already subscribed to
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @param int $user_id: the id of the user
 * @return array an array containing all the information of the courses of the given user
*/
function get_courses_of_user($user_id) {
    $table_course       = Database::get_main_table(TABLE_MAIN_COURSE);
    $table_course_user  = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    // Secondly we select the courses that are in a category (user_course_cat <> 0) and sort these according to the sort of the category
    $user_id = intval($user_id);
    $sql_select_courses = "SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
                                course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
                                course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
                                FROM    $table_course       course,
                                        $table_course_user  course_rel_user
                                WHERE course.code = course_rel_user.course_code
                                AND   course_rel_user.user_id = '".$user_id."'
                                AND course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                                ORDER BY course_rel_user.sort ASC";
    $result = Database::query($sql_select_courses);
    $courses = array();
    while ($row = Database::fetch_array($result)) {
        // We only need the database name of the course.
        $courses[$row['k']] = array('db' => $row['db'], 'code' => $row['k'], 'visual_code' => $row['vc'], 'title' => $row['i'], 'directory' => $row['dir'], 'status' => $row['status'], 'tutor' => $row['t'], 'subscribe' => $row['subscr'], 'unsubscribe' => $row['unsubscr'], 'sort' => $row['sort'], 'user_course_category' => $row['user_course_cat']);
    }
    return $courses;
}


function show_right_block($title, $content, $class = '') {    
    $html = '';  
        $html.= '<div id="menu" class="menu">';    
            $html.= '<div class="menusection '.$class.' ">';
                if (!empty($title)) {
                    $html.= '<span class="menusectioncaption">'.$title.'</span>';
                }        
                $html.= $content;
            $html.= '</div>';        
        $html.= '</div>';   
    return $html;
}
