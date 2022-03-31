<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @package chamilo.main
 */
define('CHAMILO_HOMEPAGE', true);
define('CHAMILO_LOAD_WYSIWYG', false);

/* Flag forcing the 'current course' reset, as we're not inside a course anymore. */
// Maybe we should change this into an api function? an example: CourseManager::unset();
$cidReset = true;
require_once 'main/inc/global.inc.php';

// The section (for the tabs).
$this_section = SECTION_CAMPUS; //rewritten below if including HTML file
$includeFile = !empty($_GET['include']);
if ($includeFile) {
    $this_section = SECTION_INCLUDE;
} elseif (api_get_configuration_value('plugin_redirection_enabled')) {
    RedirectionPlugin::redirectUser(api_get_user_id());
}

$header_title = null;
if (!api_is_anonymous()) {
    $header_title = ' ';
}

$controller = new IndexManager($header_title);
//Actions
$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);
if (!empty($_GET['logout'])) {
    $redirect = !empty($_GET['no_redirect']) ? false : true;
    // pass $logoutInfo defined in local.inc.php
    $controller->logout($redirect, $logoutInfo);
}
/**
 * Registers in the track_e_default table (view in important activities in admin
 * interface) a possible attempted break in, sending auth data through get.
 *
 * @todo This piece of code should probably move to local.inc.php where the
 * actual login / logout procedure is handled.
 * The real use of this code block should be seriously considered as well.
 * This form should just use a security token and get done with it.
 */
if (isset($_GET['submitAuth']) && $_GET['submitAuth'] == 1) {
    $i = api_get_anonymous_id();
    Event::addEvent(
        LOG_ATTEMPTED_FORCED_LOGIN,
        'tried_hacking_get',
        $_SERVER['REMOTE_ADDR'].(empty($_POST['login']) ? '' : '/'.$_POST['login']),
        null,
        $i
    );
    echo 'Attempted breakin - sysadmins notified.';
    session_destroy();
    exit();
}
// Delete session item necessary to check for legal terms
if (api_get_setting('allow_terms_conditions') === 'true') {
    Session::erase('term_and_condition');
}
//If we are not logged in and customapages activated
if (!api_user_is_login() && CustomPages::enabled()) {
    if (Request::get('loggedout')) {
        CustomPages::display(CustomPages::LOGGED_OUT);
    } else {
        CustomPages::display(CustomPages::INDEX_UNLOGGED);
    }
}
/**
 * @todo This piece of code should probably move to local.inc.php where the
 * actual login procedure is handled.
 * @todo Check if this code is used. I think this code is never executed because
 * after clicking the submit button the code does the stuff
 * in local.inc.php and then redirects to index.php or user_portal.php depending
 * on api_get_setting('page_after_login').
 */
if (!empty($_POST['submitAuth'])) {
    // The user has been already authenticated, we are now to find the last login of the user.
    if (isset($_user['user_id'])) {
        $track_login_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $sql = "SELECT UNIX_TIMESTAMP(login_date)
                FROM $track_login_table
                WHERE login_user_id = '".$_user['user_id']."'
                ORDER BY login_date DESC LIMIT 1";
        $result_last_login = Database::query($sql);
        if (!$result_last_login) {
            if (Database::num_rows($result_last_login) > 0) {
                $user_last_login_datetime = Database::fetch_array($result_last_login);
                $user_last_login_datetime = $user_last_login_datetime[0];
                Session::write('user_last_login_datetime', $user_last_login_datetime);
            }
        }
    }
} else {
    // Only if login form was not sent because if the form is sent the user was already on the page.
    Event::open();
}

if (!api_is_anonymous()) {
    $url = api_get_configuration_value('redirect_index_to_url_for_logged_users');
    if (!empty($url)) {
        header("Location: $url");
        exit;
    }
}

if (api_get_setting('display_categories_on_homepage') === 'true') {
    $controller->tpl->assign('course_category_block', $controller->return_courses_in_categories());
}
$controller->set_login_form();
//@todo move this inside the IndexManager

if (!api_is_anonymous()) {
    $controller->tpl->assign('profile_block', $controller->return_profile_block());
    $controller->tpl->assign('user_image_block', $controller->return_user_image_block());
    $controller->tpl->assign('course_block', $controller->return_course_block());
}
$hotCourses = '';
$announcements_block = '';

// Display the Site Use Cookie Warning Validation
$useCookieValidation = api_get_setting('cookie_warning');

if ($useCookieValidation === 'true') {
    if (isset($_POST['acceptCookies'])) {
        api_set_site_use_cookie_warning_cookie();
    } elseif (!api_site_use_cookie_warning_cookie_exist()) {
        if (Template::isToolBarDisplayedForUser()) {
            $controller->tpl->assign('toolBarDisplayed', true);
        } else {
            $controller->tpl->assign('toolBarDisplayed', false);
        }
        $controller->tpl->assign('displayCookieUsageWarning', true);
    }
}
// When loading a chamilo page do not include the hot courses and news
if (!isset($_REQUEST['include'])) {
    if (api_get_setting('show_hot_courses') == 'true') {
        if (api_get_configuration_value('popular_courses_handpicked')) {
            // If the option has been set correctly, use the courses manually
            // marked as popular rather than the ones marked by users
            $hotCourses = $controller->returnPopularCoursesHandPicked();
        } else {
            $hotCourses = $controller->return_hot_courses();
        }
    }
    $announcements_block = $controller->return_announcements();

    if (api_get_configuration_value('course_catalog_display_in_home')
        && ('true' === api_get_setting('course_catalog_published') || !api_is_anonymous())
    ) {
        $userCanViewPage = CoursesAndSessionsCatalog::userCanView();

        if (CoursesAndSessionsCatalog::is(CATALOG_SESSIONS)) {
            $announcements_block .= $userCanViewPage ? CoursesAndSessionsCatalog::sessionList(true) : '';
        } else {
            $announcements_block .= $userCanViewPage
                ? CoursesAndSessionsCatalog::displayCoursesList('display_courses', '', '', true)
                : '';
        }
    }
}
if (api_get_configuration_value('show_hot_sessions') === true) {
    $hotSessions = SessionManager::getHotSessions();
    $controller->tpl->assign('hot_sessions', $hotSessions);
}
$controller->tpl->assign('hot_courses', $hotCourses);
$controller->tpl->assign('announcements_block', $announcements_block);

$allowJustification = api_get_plugin_setting('justification', 'tool_enable') === 'true';

$justification = '';
if ($allowJustification) {
    $plugin = Justification::create();
    $courseId = api_get_plugin_setting('justification', 'default_course_id');
    if (!empty($courseId)) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $link = Display::url($plugin->get_lang('SubscribeToASession'), $courseInfo['course_public_url']);
        $justification = Display::return_message($link, 'info', false);
    }
}

if ($includeFile) {
    // If we are including a static page, then home_welcome is empty
    $controller->tpl->assign('home_welcome', $justification);
    $controller->tpl->assign('home_include', $controller->return_home_page($includeFile));
} else {
    // If we are including the real homepage, then home_include is empty
    $controller->tpl->assign('home_welcome', $justification.$controller->return_home_page(false));
    $controller->tpl->assign('home_include', '');
}
$controller->tpl->assign('navigation_links', $controller->return_navigation_links());
$controller->tpl->assign('notice_block', $controller->return_notice());
$controller->tpl->assign('help_block', $controller->return_help());
$controller->tpl->assign('student_publication_block', $controller->studentPublicationBlock());
if (api_is_platform_admin() || api_is_drh()) {
    $controller->tpl->assign('skills_block', $controller->returnSkillLinks());
}
if (api_is_anonymous()) {
    $controller->tpl->setLoginBodyClass();
}
// direct login to course
if (isset($_GET['firstpage'])) {
    $firstPage = $_GET['firstpage'];
    $courseInfo = api_get_course_info($firstPage);

    if (!empty($courseInfo)) {
        api_set_firstpage_parameter($firstPage);

        // if we are already logged, go directly to course
        if (api_user_is_login()) {
            echo "<script>self.location.href='index.php?firstpage=".Security::remove_XSS($firstPage)."'</script>";
        }
    }
} else {
    api_delete_firstpage_parameter();
}

$controller->setGradeBookDependencyBar(api_get_user_id());
$controller->tpl->display_two_col_template();

// Deleting the session_id.
Session::erase('session_id');
Session::erase('id_session');
Session::erase('studentview');
api_remove_in_gradebook();
