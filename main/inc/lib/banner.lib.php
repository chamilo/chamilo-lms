<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use ChamiloSession as Session;

/**
 * Code
 * @todo use globals or parameters or add this file in the template
 * @package chamilo.include
 */

/**
 * Determines the possible tabs (=sections) that are available.
 * This function is used when creating the tabs in the third header line and
 * all the sections that do not appear there (as determined by the
 * platform admin on the Dokeos configuration settings page)
 * will appear in the right hand menu that appears on several other pages
 * @return array containing all the possible tabs
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs($courseId = null)
{
    $_course = api_get_course_info($courseId);

    $navigation = array();

    // Campus Homepage
    $navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
    $navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');
    $navigation[SECTION_CAMPUS]['key'] = 'homepage';
    $navigation[SECTION_CAMPUS]['icon'] = 'homepage.png';

    $navigation[SECTION_CATALOG]['url'] = api_get_path(WEB_PATH).'main/auth/courses.php';
    $navigation[SECTION_CATALOG]['title'] = get_lang('Courses');
    $navigation[SECTION_CATALOG]['key'] = 'catalog';
    $navigation[SECTION_CATALOG]['icon'] = 'catalog.png';

    // My Courses
    if (api_is_allowed_to_create_course()) {
        // Link to my courses for teachers
        $navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
    } else {
        // Link to my courses for students
        $navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
    }
    $navigation['mycourses']['title'] = get_lang('MyCourses');
    $navigation['mycourses']['key'] = 'my-course';
    $navigation['mycourses']['icon'] = 'my-course.png';

    // My Profile
    $navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH)
        .'auth/profile.php'
        .(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '');
    $navigation['myprofile']['title'] = get_lang('ModifyProfile');
    $navigation['myprofile']['key'] = 'profile';
    $navigation['myprofile']['icon'] = 'profile.png';
    // Link to my agenda
    $navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=personal';
    $navigation['myagenda']['title'] = get_lang('MyAgenda');
    $navigation['myagenda']['key'] = 'agenda';
    $navigation['myagenda']['icon'] = 'agenda.png';

    // Gradebook
    if (api_get_setting('gradebook_enable') == 'true') {
        $navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH)
            .'gradebook/gradebook.php'
            .(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '');
        $navigation['mygradebook']['title'] = get_lang('MyGradebook');
        $navigation['mygradebook']['key'] = 'gradebook';
        $navigation['mygradebook']['icon'] = 'gradebook.png';
    }

    // Reporting
    if (api_is_teacher() || api_is_drh() || api_is_session_admin()) {
        // Link to my space
        $navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/'
            .(api_is_drh() ? 'session.php' : '');
        $navigation['session_my_space']['title'] = get_lang('MySpace');
        $navigation['session_my_space']['key'] = 'my-space';
        $navigation['session_my_space']['icon'] = 'my-space.png';
    } else {
        if (api_is_student_boss()) {
            $navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/student.php';
            $navigation['session_my_space']['title'] = get_lang('MySpace');
            $navigation['session_my_space']['key'] = 'my-space';
            $navigation['session_my_space']['icon'] = 'my-space.png';
        } else {
            $navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH);
            // Link to my progress
            switch (api_get_setting('gamification_mode')) {
                case 1:
                    $navigation['session_my_progress']['url'] .= 'gamification/my_progress.php';
                    break;
                default:
                    $navigation['session_my_progress']['url'] .= 'auth/my_progress.php';
            }

            $navigation['session_my_progress']['title'] = get_lang('MyProgress');
            $navigation['session_my_progress']['key'] = 'my-progress';
            $navigation['session_my_progress']['icon'] = 'my-progress.png';
        }
    }

    // Social
    if (api_get_setting('allow_social_tool') == 'true') {
        $navigation['social']['url'] = api_get_path(WEB_CODE_PATH).'social/home.php';
        $navigation['social']['title'] = get_lang('SocialNetwork');
        $navigation['social']['key'] = 'social-network';
        $navigation['social']['icon'] = 'social-network.png';
    }

    // Dashboard
    if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
        $navigation['dashboard']['url'] = api_get_path(WEB_CODE_PATH).'dashboard/index.php';
        $navigation['dashboard']['title'] = get_lang('Dashboard');
        $navigation['dashboard']['key'] = 'dashboard';
        $navigation['dashboard']['icon'] = 'dashboard.png';
    }

    // Reports
    /*
	if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
        $navigation['reports']['url'] = api_get_path(WEB_CODE_PATH).'reports/index.php';
        $navigation['reports']['title'] = get_lang('Reports');
	}*/

    // Custom Tabs See BT#7180
    $customTabs = getCustomTabs();
    if (!empty($customTabs)) {
        foreach ($customTabs as $tab) {
            if (api_get_setting($tab['variable'], $tab['subkey']) == 'true') {
                if (!empty($tab['comment']) && $tab['comment'] !== 'ShowTabsComment') {
                    $navigation[$tab['subkey']]['url'] = $tab['comment'];
                    // $tab['title'] value must be included in trad4all.inc.php
                    $navigation[$tab['subkey']]['title'] = get_lang($tab['title']);
                    $navigation[$tab['subkey']]['key'] = $tab['subkey'];
                }
            }
        }
    }
    // End Custom Tabs

    // Platform administration
    if (api_is_platform_admin(true)) {
        $navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
        $navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
        $navigation['platform_admin']['key'] = 'admin';
        $navigation['platform_admin']['icon'] = 'admin.png';
    }

    return $navigation;
}

/**
 * This function returns the custom tabs
 *
 * @return array
 */
function getCustomTabs()
{
    $tableSettingsCurrent = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = "SELECT * FROM $tableSettingsCurrent
            WHERE 
                variable = 'show_tabs' AND
                subkey like 'custom_tab_%'";
    $result = Database::query($sql);
    $customTabs = array();
    while ($row = Database::fetch_assoc($result)) {
        $shouldAdd = true;

        if (strpos($row['subkey'], Plugin::TAB_FILTER_NO_STUDENT) !== false && api_is_student()) {
            $shouldAdd = false;
        } elseif (strpos($row['subkey'], Plugin::TAB_FILTER_ONLY_STUDENT) !== false && !api_is_student()) {
            $shouldAdd = false;
        }

        if ($shouldAdd) {
            $customTabs[] = $row;
        }
    }

    return $customTabs;
}

/**
 * Return the active logo of the portal, based on a series of settings
 * @param string $theme The name of the theme folder from web/css/themes/
 * @return string HTML string with logo as an HTML element
 */
function return_logo($theme = '')
{
    $siteName = api_get_setting('siteName');

    return ChamiloApi::getPlatformLogo(
        $theme,
        [
            'title' => $siteName,
            'class' => 'img-responsive',
            'id' => 'header-logo',
        ]
    );
}

/**
 * Return HTML string of a list as <li> items
 * @return string
 */
function returnNotificationMenu()
{
    $_course = api_get_course_info();
    $course_id = 0;
    if (!empty($_course)) {
        $course_id = $_course['code'];
    }

    $user_id = api_get_user_id();

    $html = '';

    if ((api_get_setting('showonline', 'world') == 'true' && !$user_id) ||
        (api_get_setting('showonline', 'users') == 'true' && $user_id) ||
        (api_get_setting('showonline', 'course') == 'true' && $user_id && $course_id)
    ) {
        $number = getOnlineUsersCount();
        $number_online_in_course = getOnlineUsersInCourseCount($user_id, $_course);

        // Display the who's online of the platform
        if ($number &&
            (api_get_setting('showonline', 'world') == 'true' && !$user_id) ||
            (api_get_setting('showonline', 'users') == 'true' && $user_id)
        ) {
            $html .= '<li><a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_self" title="'
                .get_lang('UsersOnline').'" >'
                .Display::return_icon('user.png', get_lang('UsersOnline'), array(), ICON_SIZE_TINY)
                .' '.$number.'</a></li>';
        }

        // Display the who's online for the course
        if (
            $number_online_in_course &&
            (
                is_array($_course) &&
                api_get_setting('showonline', 'course') == 'true' && isset($_course['sysCode'])
            )
        ) {
            $html .= '<li><a href="'.api_get_path(WEB_PATH).'whoisonline.php?cidReq='.$_course['sysCode']
                .'" target="_self">'
                .Display::return_icon('course.png', get_lang('UsersOnline').' '.get_lang('InThisCourse'), array(), ICON_SIZE_TINY)
                .' '.$number_online_in_course.' </a></li>';
        }

        if (isset($user_id) && api_get_session_id() != 0) {
            $html .= '<li><a href="'.api_get_path(WEB_PATH)
                .'whoisonlinesession.php?id_coach='.$user_id.'&amp;referer='.urlencode($_SERVER['REQUEST_URI'])
                .'" target="_self">'
                .Display::return_icon('session.png', get_lang('UsersConnectedToMySessions'), array(), ICON_SIZE_TINY)
                .'</a></li>';
        }
    }

    return $html;
}

/**
 * Return an array with different navigation mennu elements
 * @return array [menu_navigation[], navigation[], possible_tabs[]]
 */
function return_navigation_array()
{
    $navigation = array();
    $menu_navigation = array();
    $possible_tabs = get_tabs();

    // Campus Homepage
    if (api_get_setting('show_tabs', 'campus_homepage') == 'true') {
        $navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    } else {
        $menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    }

    if (api_get_setting('course_catalog_published') == 'true' && api_is_anonymous()) {
        $navigation[SECTION_CATALOG] = $possible_tabs[SECTION_CATALOG];
    }

    if (api_get_user_id() && !api_is_anonymous()) {
        // My Courses
        if (api_get_setting('show_tabs', 'my_courses') == 'true') {
            $navigation['mycourses'] = $possible_tabs['mycourses'];
        } else {
            $menu_navigation['mycourses'] = $possible_tabs['mycourses'];
        }

        // My Profile
        if (api_get_setting('show_tabs', 'my_profile') == 'true' &&
            api_get_setting('allow_social_tool') != 'true'
        ) {
            $navigation['myprofile'] = $possible_tabs['myprofile'];
        } else {
            $menu_navigation['myprofile'] = $possible_tabs['myprofile'];
        }

        // My Agenda
        if (api_get_setting('show_tabs', 'my_agenda') == 'true') {
            $navigation['myagenda'] = $possible_tabs['myagenda'];
        } else {
            $menu_navigation['myagenda'] = $possible_tabs['myagenda'];
        }

        // Gradebook
        if (api_get_setting('gradebook_enable') == 'true') {
            if (api_get_setting('show_tabs', 'my_gradebook') == 'true') {
                $navigation['mygradebook'] = $possible_tabs['mygradebook'];
            } else {
                $menu_navigation['mygradebook'] = $possible_tabs['mygradebook'];
            }
        }

        // Reporting
        if (api_get_setting('show_tabs', 'reporting') == 'true') {
            if (api_is_teacher() || api_is_drh() || api_is_session_admin() || api_is_student_boss()) {
                $navigation['session_my_space'] = $possible_tabs['session_my_space'];
            } else {
                $navigation['session_my_space'] = $possible_tabs['session_my_progress'];
            }
        } else {
            if (api_is_teacher() || api_is_drh() || api_is_session_admin() || api_is_student_boss()) {
                $menu_navigation['session_my_space'] = $possible_tabs['session_my_space'];
            } else {
                $menu_navigation['session_my_space'] = $possible_tabs['session_my_progress'];
            }
        }

        // Social Networking
        if (api_get_setting('show_tabs', 'social') == 'true') {
            if (api_get_setting('allow_social_tool') == 'true') {
                $navigation['social'] = isset($possible_tabs['social']) ? $possible_tabs['social'] : null;
            }
        } else {
            $menu_navigation['social'] = isset($possible_tabs['social']) ? $possible_tabs['social'] : null;
        }

        // Dashboard
        if (api_get_setting('show_tabs', 'dashboard') == 'true') {
            if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
                $navigation['dashboard'] = isset($possible_tabs['dashboard']) ? $possible_tabs['dashboard'] : null;
            }
        } else {
            $menu_navigation['dashboard'] = isset($possible_tabs['dashboard']) ? $possible_tabs['dashboard'] : null;
        }

        ///if (api_is_student()) {
        if (true) {
            $params = array('variable = ? AND subkey = ?' => ['status', 'studentfollowup']);
            $result = api_get_settings_params_simple($params);
            $plugin = StudentFollowUpPlugin::create();
            if (!empty($result) && $result['selected_value'] === 'installed') {
                // Students
                $url = api_get_path(WEB_PLUGIN_PATH).'studentfollowup/posts.php';

                if (api_is_platform_admin() || api_is_drh() || api_is_teacher()) {
                    $url = api_get_path(WEB_PLUGIN_PATH).'studentfollowup/my_students.php';
                }
                $navigation['follow_up']['url'] = $url;
                $navigation['follow_up']['title'] = $plugin->get_lang('CareDetailView');
                $navigation['follow_up']['key'] = 'homepage';
                $navigation['follow_up']['icon'] = 'homepage.png';
            }
        }

        // Administration
        if (api_is_platform_admin(true)) {
            if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
                $navigation['platform_admin'] = $possible_tabs['platform_admin'];
            } else {
                $menu_navigation['platform_admin'] = $possible_tabs['platform_admin'];
            }
        }

        // Reports
        if (!empty($possible_tabs['reports'])) {
            if (api_get_setting('show_tabs', 'reports') == 'true') {
                if ((api_is_platform_admin() || api_is_drh() || api_is_session_admin())
                        && Rights::hasRight('show_tabs:reports')
                ) {
                    $navigation['reports'] = $possible_tabs['reports'];
                }
            } else {
                $menu_navigation['reports'] = $possible_tabs['reports'];
            }
        }

        // Custom tabs
        $customTabs = getCustomTabs();

        if (!empty($customTabs)) {
            foreach ($customTabs as $tab) {
                if (api_get_setting($tab['variable'], $tab['subkey']) == 'true' &&
                    isset($possible_tabs[$tab['subkey']])
                ) {
                    $possible_tabs[$tab['subkey']]['url'] = api_get_path(WEB_PATH)
                        .$possible_tabs[$tab['subkey']]['url'];
                    $navigation[$tab['subkey']] = $possible_tabs[$tab['subkey']];
                } else {
                    if (isset($possible_tabs[$tab['subkey']])) {
                        $possible_tabs[$tab['subkey']]['url'] = api_get_path(WEB_PATH)
                            .$possible_tabs[$tab['subkey']]['url'];
                        $menu_navigation[$tab['subkey']] = $possible_tabs[$tab['subkey']];
                    }
                }
            }
        }
    } else {
        // Show custom tabs that are specifically marked as public
        $customTabs = getCustomTabs();
        if (!empty($customTabs)) {
            foreach ($customTabs as $tab) {
                if (api_get_setting($tab['variable'], $tab['subkey']) == 'true' &&
                    isset($possible_tabs[$tab['subkey']]) &&
                    api_get_plugin_setting(strtolower(str_replace('Tabs', '', $tab['subkeytext'])), 'public_main_menu_tab') == 'true'
                ) {
                    $possible_tabs[$tab['subkey']]['url'] = api_get_path(WEB_PATH).$possible_tabs[$tab['subkey']]['url'];
                    $navigation[$tab['subkey']] = $possible_tabs[$tab['subkey']];
                } else {
                    if (isset($possible_tabs[$tab['subkey']])) {
                        $possible_tabs[$tab['subkey']]['url'] = api_get_path(WEB_PATH).$possible_tabs[$tab['subkey']]['url'];
                        $menu_navigation[$tab['subkey']] = $possible_tabs[$tab['subkey']];
                    }
                }
            }
        }
    }

    return array(
        'menu_navigation' => $menu_navigation,
        'navigation' => $navigation,
        'possible_tabs' => $possible_tabs,
    );
}

/**
 * Return the navigation menu elements as a flat array
 * @return array
 */
function menuArray()
{
    $mainNavigation = return_navigation_array();
    unset($mainNavigation['possible_tabs']);
    unset($mainNavigation['menu_navigation']);
    //$navigation = $navigation['navigation'];
    // Get active language
    $lang = api_get_setting('platformLanguage');
    if (!empty($_SESSION['user_language_choice'])) {
        $lang = $_SESSION['user_language_choice'];

    } elseif (!empty($_SESSION['_user']['language'])) {
        $lang = $_SESSION['_user']['language'];
    }

    // Preparing home folder for multiple urls
    if (api_get_multiple_access_url()) {
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            // If not a dead URL
            $urlInfo = api_get_access_url($access_url_id);
            $url = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $urlInfo['url']));
            $cleanUrl = api_replace_dangerous_char($url);
            $cleanUrl = str_replace('/', '-', $cleanUrl);
            $cleanUrl .= '/';
            $homepath = api_get_path(SYS_HOME_PATH).$cleanUrl; //homep for Home Path

            //we create the new dir for the new sites
            if (!is_dir($homepath)) {
                mkdir($homepath, api_get_permissions_for_new_directories());
            }
        }
    } else {
        $homepath = api_get_path(SYS_HOME_PATH);
    }
    $ext = '.html';
    $menuTabs = 'home_tabs';
    $menuTabsLoggedIn = 'home_tabs_logged_in';
    $pageContent = '';
    // Get the extra page content, containing the links to add to the tabs
    if (is_file($homepath.$menuTabs.'_'.$lang.$ext) && is_readable($homepath.$menuTabs.'_'.$lang.$ext)) {
        $pageContent = @(string) file_get_contents($homepath.$menuTabs.'_'.$lang.$ext);
    } elseif (is_file($homepath.$menuTabs.$lang.$ext) && is_readable($homepath.$menuTabs.$lang.$ext)) {
        $pageContent = @(string) file_get_contents($homepath.$menuTabs.$lang.$ext);
    }
    // Sanitize page content
    $pageContent = api_to_system_encoding($pageContent, api_detect_encoding(strip_tags($pageContent)));
    $open = str_replace('{rel_path}', api_get_path(REL_PATH), $pageContent);
    $open = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
    // Get the extra page content, containing the links to add to the tabs
    //  that are only for users already logged in
    $openMenuTabsLoggedIn = '';
    if (api_get_user_id() && !api_is_anonymous()) {
        if (is_file($homepath.$menuTabsLoggedIn.'_'.$lang.$ext) && is_readable(
                $homepath.$menuTabsLoggedIn.'_'.$lang.$ext
            )
        ) {
            $pageContent = @(string) file_get_contents($homepath.$menuTabsLoggedIn.'_'.$lang.$ext);
            $pageContent = str_replace('::private', '', $pageContent);
        } elseif (is_file($homepath.$menuTabsLoggedIn.$lang.$ext) && is_readable(
                $homepath.$menuTabsLoggedIn.$lang.$ext
            )
        ) {
            $pageContent = @(string) file_get_contents($homepath.$menuTabsLoggedIn.$lang.$ext);
            $pageContent = str_replace('::private', '', $pageContent);
        }

        $pageContent = api_to_system_encoding($pageContent, api_detect_encoding(strip_tags($pageContent)));
        $openMenuTabsLoggedIn = str_replace('{rel_path}', api_get_path(REL_PATH), $pageContent);
        $openMenuTabsLoggedIn = api_to_system_encoding(
            $openMenuTabsLoggedIn,
            api_detect_encoding(strip_tags($openMenuTabsLoggedIn))
        );
    }
    if (!empty($open) || !empty($openMenuTabsLoggedIn)) {
        if (strpos($open.$openMenuTabsLoggedIn, 'show_menu') === false) {
            if (api_is_anonymous()) {
                $mainNavigation['navigation'][SECTION_CAMPUS] = null;
            }
        } else {
            $list = explode("\n", api_get_user_id() && !api_is_anonymous() ? $openMenuTabsLoggedIn : $open);

            foreach ($list as $link) {
                if (strpos($link, 'class="hide_menu"') !== false) {
                    continue;
                }

                $matches = array();
                $match = preg_match('$href="([^"]*)" target="([^"]*)">([^<]*)</a>$', $link, $matches);

                if (!$match) {
                    continue;
                }

                $mainNavigation['navigation'][$matches[3]] = array(
                    'url' => $matches[1],
                    'target' => $matches[2],
                    'title' => $matches[3],
                    'key' => 'page-'.str_replace(' ', '-', strtolower($matches[3])),
                );
            }
        }
    }

    if (count($mainNavigation['navigation']) > 0) {
        //$pre_lis = '';
        $activeSection = '';
        foreach ($mainNavigation['navigation'] as $section => $navigation_info) {
            $key = (!empty($navigation_info['key']) ? 'tab-'.$navigation_info['key'] : '');

            if (isset($GLOBALS['this_section'])) {
                $tempSection = $section;
                if ($section == 'social') {
                    $tempSection = 'social-network';
                }
                if ($tempSection == $GLOBALS['this_section']) {
                    $activeSection = $section;
                }
                // If we're on the index page and a specific extra link has been
                // loaded
                if ($GLOBALS['this_section'] == SECTION_CAMPUS) {
                    if (!empty($_GET['include'])) {
                        $name = str_replace(' ', '-', strtolower($navigation_info['title'])).'_'.$lang.$ext;
                        if (strtolower($_GET['include']) == $name) {
                            $activeSection = $section;
                        }
                    }
                }
            } else {
                $current = '';
            }
            $mainNavigation['navigation'][$section]['current'] = '';
        }
        if (!empty($activeSection)) {
            $mainNavigation['navigation'][$activeSection]['current'] = 'active';
        }

    }
    unset($mainNavigation['navigation']['myprofile']);

    return $mainNavigation['navigation'];
}

/**
 * Return the breadcrumb menu elements as an array of <li> items
 * @param array $interbreadcrumb The elements to add to the breadcrumb
 * @param string $language_file Deprecated
 * @param string $nameTools The name of the current tool (not linked)
 * @return string HTML string of <li> items
 */
function return_breadcrumb($interbreadcrumb, $language_file, $nameTools)
{
    /** @var \Chamilo\CoreBundle\Entity\Session $session */
    $session = Database::getManager()
        ->find('ChamiloCoreBundle:Session', api_get_session_id());
    $_course = api_get_course_info();
    $user_id = api_get_user_id();
    $course_id = 0;
    if (!empty($_course)) {
        $course_id = $_course['real_id'];
    }

    $additonalBlocks = '';

    /*  Plugins for banner section */
    $web_course_path = api_get_path(WEB_COURSE_PATH);

    /* If the user is a coach he can see the users who are logged in its session */
    $navigation = array();

    // part 1: Course Homepage. If we are in a course then the first breadcrumb is a link to the course homepage
    // hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
    $my_session_name = $session ? ' ('.cut($session->getName(), MAX_LENGTH_BREADCRUMB).')' : null;

    if (!empty($_course) && !isset($_GET['hide_course_breadcrumb'])) {
        $_course['name'] = api_htmlentities($_course['name']);
        $course_title = cut($_course['name'], MAX_LENGTH_BREADCRUMB);

        switch (api_get_setting('breadcrumbs_course_homepage')) {
            case 'get_lang':
                $itemTitle = Display::return_icon('home.png', get_lang('CourseHomepageLink'), [], ICON_SIZE_TINY);
                break;
            case 'course_code':
                $itemTitle = Display::return_icon('home.png', $_course['official_code'], [], ICON_SIZE_TINY)
                    .' '.$_course['official_code'];
                break;
            case 'session_name_and_course_title':
                //no break
            default:
                $itemTitle = Display::return_icon('home.png', $_course['name'].$my_session_name, [], ICON_SIZE_TINY)
                    .' '.$course_title.$my_session_name;

                if (
                    $session && ($session->getDuration() && !api_is_allowed_to_edit())
                ) {
                    $daysLeft = SessionManager::getDayLeftInSession(
                        ['id' => $session->getId(), 'duration' => $session->getDuration()],
                        $user_id
                    );

                    $additonalBlocks .= Display::return_message(
                        sprintf(get_lang('SessionDurationXDaysLeft'), $daysLeft),
                        'information'
                    );
                }
                break;
        }

        /**
         * @todo could be useful adding the My courses in the breadcrumb
         * $navigation_item_my_courses['title'] = get_lang('MyCourses');
         * $navigation_item_my_courses['url'] = api_get_path(WEB_PATH).'user_portal.php';
         * $navigation[] = $navigation_item_my_courses;
         */
        $navigation[] = [
            'url' => $web_course_path.$_course['path'].'/index.php'.($session ? '?id_session='.$session->getId() : ''),
            'title' => $itemTitle
        ];
    }

    /* part 2: Interbreadcrumbs. If there is an array $interbreadcrumb
    defined then these have to appear before the last breadcrumb
    (which is the tool itself)*/
    if (isset($interbreadcrumb) && is_array($interbreadcrumb)) {
        foreach ($interbreadcrumb as $breadcrumb_step) {
            if (isset($breadcrumb_step['type']) && $breadcrumb_step['type'] == 'right') {
                continue;
            }
            if ($breadcrumb_step['url'] != '#') {
                $sep = strrchr($breadcrumb_step['url'], '?') ? '&' : '?';
                $courseParams = strpos($breadcrumb_step['url'], 'cidReq') === false ? api_get_cidreq() : '';
                $navigation_item['url'] = $breadcrumb_step['url'].$sep.$courseParams;
            } else {
                $navigation_item['url'] = '#';
            }
            $navigation_item['title'] = $breadcrumb_step['name'];
            // titles for shared folders
            if ($breadcrumb_step['name'] == 'shared_folder') {
                $navigation_item['title'] = get_lang('UserFolders');
            } elseif (strstr($breadcrumb_step['name'], 'shared_folder_session_')) {
                $navigation_item['title'] = get_lang('UserFolders');
            } elseif (strstr($breadcrumb_step['name'], 'sf_user_')) {
                $userinfo = api_get_user_info(substr($breadcrumb_step['name'], 8));
                $navigation_item['title'] = $userinfo['complete_name'];
            } elseif ($breadcrumb_step['name'] == 'chat_files') {
                $navigation_item['title'] = get_lang('ChatFiles');
            } elseif ($breadcrumb_step['name'] == 'images') {
                $navigation_item['title'] = get_lang('Images');
            } elseif ($breadcrumb_step['name'] == 'video') {
                $navigation_item['title'] = get_lang('Video');
            } elseif ($breadcrumb_step['name'] == 'audio') {
                $navigation_item['title'] = get_lang('Audio');
            } elseif ($breadcrumb_step['name'] == 'flash') {
                $navigation_item['title'] = get_lang('Flash');
            } elseif ($breadcrumb_step['name'] == 'gallery') {
                $navigation_item['title'] = get_lang('Gallery');
            }
            // Fixes breadcrumb title now we applied the Security::remove_XSS and
            // we cut the string depending of the MAX_LENGTH_BREADCRUMB value
            $navigation_item['title'] = cut($navigation_item['title'], MAX_LENGTH_BREADCRUMB);
            $navigation_item['title'] = Security::remove_XSS($navigation_item['title']);

            $navigation[] = $navigation_item;
        }
    }

    $navigation_right = array();

    if (isset($interbreadcrumb) && is_array($interbreadcrumb)) {
        foreach ($interbreadcrumb as $breadcrumb_step) {
            if (isset($breadcrumb_step['type']) && $breadcrumb_step['type'] == 'right') {
                if ($breadcrumb_step['url'] != '#') {
                    $sep = (strrchr($breadcrumb_step['url'], '?') ? '&amp;' : '?');
                    $navigation_item['url'] = $breadcrumb_step['url'].$sep.api_get_cidreq();
                } else {
                    $navigation_item['url'] = '#';
                }
                $breadcrumb_step['title'] = cut($navigation_item['title'], MAX_LENGTH_BREADCRUMB);
                $breadcrumb_step['title'] = Security::remove_XSS($navigation_item['title']);
                $navigation_right[] = $breadcrumb_step;
            }
        }
    }

    // part 3: The tool itself. If we are on the course homepage we do not want
    // to display the title of the course because this
    // is the same as the first part of the breadcrumbs (see part 1)
    if (isset($nameTools)) {
        $navigation_item['url'] = '#';
        $navigation_item['title'] = $nameTools;
        $navigation[] = $navigation_item;
    }

    $final_navigation = array();
    $counter = 0;
    foreach ($navigation as $index => $navigation_info) {
        if (!empty($navigation_info['title'])) {
            if ($navigation_info['url'] == '#') {
                $final_navigation[$index] = $navigation_info['title'];
            } else {
                $final_navigation[$index] = '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
            }
            $counter++;
        }
    }

    $html = '';

    /* Part 4 . Show the teacher view/student view button at the right of the breadcrumb */
    $view_as_student_link = null;
    if ($user_id && isset($course_id)) {
        if ((
                api_is_course_admin() ||
                api_is_platform_admin() ||
                api_is_coach(null, null, false)
            ) &&
            api_get_setting('student_view_enabled') === 'true' && api_get_course_info()
        ) {
            $view_as_student_link = api_display_tool_view_option();

            // Only show link if LP can be editable
            /** @var learnpath $learnPath */
            $learnPath = Session::read('oLP');
            if (!empty($learnPath) && !empty($view_as_student_link)) {
                if ((int) $learnPath->get_lp_session_id() != (int) api_get_session_id()) {
                    $view_as_student_link = '';
                }
            }
        }
    }

    if (!empty($final_navigation)) {
        $lis = '';
        $i = 0;
        $final_navigation_count = count($final_navigation);
        if (!empty($final_navigation)) {
            // $home_link.= '<span class="divider">/</span>';
            if (!empty($home_link)) {
                $lis .= Display::tag('li', $home_link);
            }

            foreach ($final_navigation as $bread) {
                $bread_check = trim(strip_tags($bread));
                if (!empty($bread_check)) {
                    if ($final_navigation_count - 1 > $i) {
                        $bread .= '';
                    }
                    $lis .= Display::tag('li', $bread, array('class' => 'active'));
                    $i++;
                }
            }
        } else {
            if (!empty($home_link)) {
                $lis .= Display::tag('li', $home_link);
            }
        }

        // View as student/teacher link
        if (!empty($view_as_student_link)) {
            $html .= Display::tag('div', $view_as_student_link, array('id' => 'view_as_link', 'class' => 'pull-right'));
        }

        if (!empty($navigation_right)) {
            foreach ($navigation_right as $item) {
                $extra_class = isset($item['class']) ? $item['class'] : null;
                $lis .= Display::tag('li', $item['title'], array('class' => $extra_class.' pull-right'));
            }
        }

        if (!empty($lis)) {
            $html .= Display::tag('ul', $lis, array('class' => 'breadcrumb'));
        }
    }

    return $html.$additonalBlocks;
}

/**
 * Helper function to get the number of users online, using cache if available
 * @return  int     The number of users currently online
 */
function getOnlineUsersCount()
{
    $number = 0;
    $cacheAvailable = api_get_configuration_value('apc');
    if ($cacheAvailable === true) {
        $apcVar = api_get_configuration_value('apc_prefix').'my_campus_whoisonline_count_simple';
        if (apcu_exists($apcVar)) {
            $number = apcu_fetch($apcVar);
        } else {
            $number = who_is_online_count(api_get_setting('time_limit_whosonline'));
            apcu_store($apcVar, $number, 15);
        }
    } else {
        $number = who_is_online_count(api_get_setting('time_limit_whosonline'));
    }

    return $number;
}

/**
 * Helper function to get the number of users online in a course, using cache if available
 * @param   int $userId The user ID
 * @param   array $_course The course details
 * @return  int     The number of users currently online
 */
function getOnlineUsersInCourseCount($userId, $_course)
{
    $cacheAvailable = api_get_configuration_value('apc');
    $numberOnlineInCourse = 0;
    if (!empty($_course['id'])) {
        if ($cacheAvailable === true) {
            $apcVar = api_get_configuration_value('apc_prefix').'my_campus_whoisonline_count_simple_'.$_course['id'];
            if (apcu_exists($apcVar)) {
                $numberOnlineInCourse = apcu_fetch($apcVar);
            } else {
                $numberOnlineInCourse = who_is_online_in_this_course_count(
                    $userId,
                    api_get_setting('time_limit_whosonline'),
                    $_course['id']
                );
                apcu_store(
                    $apcVar,
                    $numberOnlineInCourse,
                    15
                );
            }
        } else {
            $numberOnlineInCourse = who_is_online_in_this_course_count(
                $userId,
                api_get_setting('time_limit_whosonline'),
                $_course['id']
            );
        }
    }

    return $numberOnlineInCourse;
}
