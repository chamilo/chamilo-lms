<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use ChamiloSession as Session;

/**
 * Code.
 *
 * @todo use globals or parameters or add this file in the template
 *
 * @package chamilo.include
 */

/**
 * Determines the possible tabs (=sections) that are available.
 * This function is used when creating the tabs in the third header line and
 * all the sections that do not appear there (as determined by the
 * platform admin on the Dokeos configuration settings page)
 * will appear in the right hand menu that appears on several other pages.
 *
 * @return array containing all the possible tabs
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs($courseId = null)
{
    $courseInfo = api_get_course_info($courseId);

    $navigation = [];

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
    $navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'
        .(!empty($courseInfo['path']) ? '?coursePath='.$courseInfo['path'].'&amp;courseCode='.$courseInfo['official_code'] : '');
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
            .(!empty($courseInfo['path']) ? '?coursePath='.$courseInfo['path'].'&amp;courseCode='.$courseInfo['official_code'] : '');
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
 * This function returns the custom tabs.
 *
 * @return array
 */
function getCustomTabs()
{
    static $customTabs = null;

    if ($customTabs !== null) {
        return $customTabs;
    }

    $urlId = api_get_current_access_url_id();
    $isStudent = api_is_student();
    $cacheAvailable = api_get_configuration_value('apc');
    if ($cacheAvailable === true) {
        $apcVar = api_get_configuration_value('apc_prefix').'custom_tabs_url_student_'.($isStudent ? '1' : '0');
        if (apcu_exists($apcVar)) {
            return apcu_fetch($apcVar);
        }
    }
    $tableSettingsCurrent = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = "SELECT * FROM $tableSettingsCurrent
            WHERE
                variable = 'show_tabs' AND
                subkey LIKE 'custom_tab_%' AND access_url = $urlId ";
    $result = Database::query($sql);
    $customTabs = [];
    while ($row = Database::fetch_assoc($result)) {
        $shouldAdd = true;
        if (strpos($row['subkey'], Plugin::TAB_FILTER_NO_STUDENT) !== false && $isStudent) {
            $shouldAdd = false;
        } elseif (strpos($row['subkey'], Plugin::TAB_FILTER_ONLY_STUDENT) !== false && !$isStudent) {
            $shouldAdd = false;
        }

        if ($shouldAdd) {
            $customTabs[] = $row;
        }
    }
    if ($cacheAvailable === true) {
        $apcVar = api_get_configuration_value('apc_prefix').'custom_tabs_url_'.$urlId.'_student_'.($isStudent ? '1' : '0');
        apcu_store($apcVar, $customTabs, 15);
    }

    return $customTabs;
}

/**
 * Return the active logo of the portal, based on a series of settings.
 *
 * @param string $theme      The name of the theme folder from web/css/themes/
 * @param bool   $responsive add class img-responsive
 *
 * @return string HTML string with logo as an HTML element
 */
function return_logo($theme = '', $responsive = true)
{
    $siteName = api_get_setting('siteName');
    $class = 'img-responsive';
    if (!$responsive) {
        $class = '';
    }

    if (api_get_configuration_value('mail_header_from_custom_course_logo') == true) {
        // check if current page is a course page
        $courseCode = api_get_course_id();

        if (!empty($courseCode)) {
            $course = api_get_course_info($courseCode);
            if (!empty($course) && !empty($course['course_email_image_large'])) {
                $image = \Display::img(
                    $course['course_email_image_large'],
                    $course['name'],
                    [
                        'title' => $course['name'],
                        'class' => $class,
                        'id' => 'header-logo',
                    ]
                );

                return \Display::url($image, $course['course_public_url']);
            }
        }
    }

    return ChamiloApi::getPlatformLogo(
        $theme,
        [
            'title' => $siteName,
            'class' => $class,
            'id' => 'header-logo',
        ]
    );
}

/**
 * Check if user have access to "who is online" page.
 *
 * @param int $userId   The user for whom we want to check
 * @param int $courseId The course ID for if we want the number of users in the course. Set to 0 for "out of a course context". Leave empty if you want the PHP session info to be used.
 *
 * @return bool
 */
function accessToWhoIsOnline($userId = null, $courseId = null)
{
    if (empty($userId)) {
        $userId = api_get_user_id();
    }
    // If we received 0, treat it as "no course" instead of searching again
    if ($courseId === null) {
        $courseId = api_get_course_int_id();
    }
    $access = false;

    if (true === api_get_configuration_value('whoisonline_only_for_admin') && !api_is_platform_admin()) {
        return false;
    }

    if ((api_get_setting('showonline', 'world') == 'true' && !$userId) ||
        (api_get_setting('showonline', 'users') == 'true' && $userId) ||
        (api_get_setting('showonline', 'course') == 'true' && $userId && $courseId)
    ) {
        $access = true;
        $profileList = api_get_configuration_value('allow_online_users_by_status');
        if (!empty($profileList) && isset($profileList['status'])) {
            $userInfo = api_get_user_info($userId);
            if ($userInfo['is_admin']) {
                $userInfo['status'] = PLATFORM_ADMIN;
            }
            $profileList = $profileList['status'];
            $access = false;
            if (in_array($userInfo['status'], $profileList)) {
                $access = true;
            }
        }
    }

    return $access;
}

/**
 * Return HTML string of a list as <li> items.
 *
 * @return string
 */
function returnNotificationMenu()
{
    $html = '';

    $user_id = api_get_user_id();
    $courseInfo = api_get_course_info();
    if (accessToWhoIsOnline($user_id, (!empty($courseInfo['real_id']) ?: 0))) {
        // Display the who's online of the platform
        if ((api_get_setting('showonline', 'world') == 'true' && !$user_id) ||
            (api_get_setting('showonline', 'users') == 'true' && $user_id)
        ) {
            $number = getOnlineUsersCount();
            if ($number) {
                $html .= '<li class="user-online"><a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_self" title="'
                    .get_lang('UsersOnline').'" >'
                    .Display::return_icon('user.png', get_lang('UsersOnline'), [], ICON_SIZE_TINY)
                    .' '.$number.'</a></li>';
            }
        }

        // Display the who's online for the course
        if (
            is_array($courseInfo) &&
            api_get_setting('showonline', 'course') == 'true' && isset($courseInfo['sysCode'])
        ) {
            $number_online_in_course = getOnlineUsersInCourseCount($user_id, $courseInfo);
            if ($number_online_in_course) {
                $html .= '<li class="user-online-course"><a href="'.api_get_path(WEB_PATH).'whoisonline.php?cidReq='.$courseInfo['sysCode']
                    .'" target="_self">'
                    .Display::return_icon('course.png', get_lang('UsersOnline').' '.get_lang('InThisCourse'), [], ICON_SIZE_TINY)
                    .' '.$number_online_in_course.' </a></li>';
            }
        }

        $sessionId = api_get_session_id();
        if (!empty($sessionId)) {
            $allow = api_is_platform_admin(true) ||
                api_is_coach($sessionId, null, false) ||
                SessionManager::isUserSubscribedAsStudent($sessionId, api_get_user_id());
            if ($allow) {
                $numberOnlineInSession = getOnlineUsersInSessionCount($sessionId);
                $html .= '<li class="user-online-session">
                            <a href="'.api_get_path(WEB_PATH).'whoisonlinesession.php" target="_self">'
                            .Display::return_icon('session.png', get_lang('UsersConnectedToMySessions'), [], ICON_SIZE_TINY)
                            .' '.$numberOnlineInSession.'</a></li>';
            }
        }
    }

    return $html;
}

/**
 * Return an array with different navigation mennu elements.
 *
 * @return array [menu_navigation[], navigation[], possible_tabs[]]
 */
function return_navigation_array()
{
    $navigation = [];
    $menu_navigation = [];
    $possible_tabs = get_tabs();

    // Campus Homepage
    if (api_get_setting('show_tabs', 'campus_homepage') == 'true') {
        $navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    } else {
        $menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    }

    if (api_get_setting('course_catalog_published') == 'true' && api_is_anonymous()) {
        if (true !== api_get_configuration_value('catalog_hide_public_link')) {
            $navigation[SECTION_CATALOG] = $possible_tabs[SECTION_CATALOG];
        }
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

        $installed = AppPlugin::getInstance()->isInstalled('studentfollowup');
        if ($installed) {
            $plugin = StudentFollowUpPlugin::create();
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

        // Administration
        if (api_is_platform_admin(true)) {
            if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
                $navigation['platform_admin'] = $possible_tabs['platform_admin'];
            } else {
                $menu_navigation['platform_admin'] = $possible_tabs['platform_admin'];
            }
        }

        // Custom tabs
        $customTabs = getCustomTabs();
        if (!empty($customTabs)) {
            foreach ($customTabs as $tab) {
                if (api_get_setting($tab['variable'], $tab['subkey']) == 'true' &&
                    isset($possible_tabs[$tab['subkey']])
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

    return [
        'menu_navigation' => $menu_navigation,
        'navigation' => $navigation,
        'possible_tabs' => $possible_tabs,
    ];
}

/**
 * Return the navigation menu elements as a flat array.
 *
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
        if (strpos($open.$openMenuTabsLoggedIn, 'show_menu') !== false) {
            $list = explode("\n", api_get_user_id() && !api_is_anonymous() ? $openMenuTabsLoggedIn : $open);

            foreach ($list as $link) {
                if (strpos($link, 'class="hide_menu"') !== false) {
                    continue;
                }

                $matches = [];
                $match = preg_match('$href="([^"]*)" target="([^"]*)">([^<]*)</a>$', $link, $matches);

                if (!$match) {
                    continue;
                }

                $mainNavigation['navigation'][$matches[3]] = [
                    'url' => $matches[1],
                    'target' => $matches[2],
                    'title' => $matches[3],
                    'key' => 'page-'.str_replace(' ', '-', strtolower($matches[3])),
                ];
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
 * Return the breadcrumb menu elements as an array of <li> items.
 *
 * @param array  $interbreadcrumb The elements to add to the breadcrumb
 * @param string $language_file   Deprecated
 * @param string $nameTools       The name of the current tool (not linked)
 *
 * @return string HTML string of <li> items
 */
function return_breadcrumb($interbreadcrumb, $language_file, $nameTools)
{
    // This configuration option allows you to completely hide the breadcrumb
    if (api_get_configuration_value('breadcrumb_hide') == true) {
        return '';
    }
    $courseInfo = api_get_course_info();
    $user_id = api_get_user_id();
    $additionalBlocks = '';

    /*  Plugins for banner section */
    $web_course_path = api_get_path(WEB_COURSE_PATH);

    /* If the user is a coach he can see the users who are logged in its session */
    $navigation = [];

    $sessionId = api_get_session_id();
    // part 1: Course Homepage. If we are in a course then the first breadcrumb
    // is a link to the course homepage
    if (!empty($courseInfo) && !isset($_GET['hide_course_breadcrumb'])) {
        $sessionName = '';
        if (!empty($sessionId)) {
            /** @var \Chamilo\CoreBundle\Entity\Session $session */
            $session = Database::getManager()->find('ChamiloCoreBundle:Session', $sessionId);
            $sessionName = $session ? ' ('.cut(Security::remove_XSS($session->getName()), MAX_LENGTH_BREADCRUMB).')' : '';
        }

        $courseInfo['name'] = api_htmlentities($courseInfo['name']);
        $course_title = cut($courseInfo['name'], MAX_LENGTH_BREADCRUMB);

        switch (api_get_setting('breadcrumbs_course_homepage')) {
            case 'get_lang':
                $itemTitle = Display::return_icon(
                    'home.png',
                    get_lang('CourseHomepageLink'),
                    [],
                    ICON_SIZE_TINY
                );
                break;
            case 'course_code':
                $itemTitle = Display::return_icon(
                    'home.png',
                    $courseInfo['official_code'],
                    [],
                    ICON_SIZE_TINY
                )
                .' '.$courseInfo['official_code'];
                break;
            case 'session_name_and_course_title':
            default:
                $itemTitle = Display::return_icon(
                    'home.png',
                    $courseInfo['name'].$sessionName,
                    [],
                    ICON_SIZE_TINY
                )
                .' '.$course_title.$sessionName;

                if (!empty($sessionId) && ($session->getDuration() && !api_is_allowed_to_edit())) {
                    $daysLeft = SessionManager::getDayLeftInSession(
                        ['id' => $session->getId(), 'duration' => $session->getDuration()],
                        $user_id
                    );

                    if ($daysLeft >= 0) {
                        $additionalBlocks .= Display::return_message(
                            sprintf(get_lang('SessionDurationXDaysLeft'), $daysLeft),
                            'information'
                        );
                    } else {
                        $additionalBlocks .= Display::return_message(
                            get_lang('YourSessionTimeHasExpired'),
                            'warning'
                        );
                    }
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
            'url' => $web_course_path.$courseInfo['path'].'/index.php?id_session='.$sessionId,
            'title' => $itemTitle,
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

    $navigation_right = [];
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

    $final_navigation = [];
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
    if ($user_id && !empty($courseInfo)) {
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
            if (!empty($home_link)) {
                $lis .= Display::tag('li', $home_link);
            }

            foreach ($final_navigation as $bread) {
                $bread_check = trim(strip_tags($bread));
                if (!empty($bread_check)) {
                    if ($final_navigation_count - 1 > $i) {
                        $bread .= '';
                    }
                    $lis .= Display::tag('li', $bread, ['class' => 'active']);
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
            $html .= Display::tag('div', $view_as_student_link, ['id' => 'view_as_link', 'class' => 'pull-right']);
        }

        if ($sessionId && !empty($courseInfo) &&
            (
                api_is_platform_admin()
                || (CourseManager::is_course_teacher($user_id, $courseInfo['code']))
            )
        ) {
            $url = Display::url(
                Display::return_icon('course.png', get_lang('Course')),
                $courseInfo['course_public_url'].'?id_session=0',
                ['class' => 'btn btn-default btn-sm', 'target' => '_blank']
            );
            $button = Display::tag('div', $url, ['class' => 'view-options']);
            $html .= Display::tag('div', $button, ['id' => 'view_as_link', 'class' => 'pull-right']);
        }

        if (!empty($navigation_right)) {
            foreach ($navigation_right as $item) {
                $extra_class = isset($item['class']) ? $item['class'] : null;
                $lis .= Display::tag(
                    'li',
                    $item['title'],
                    ['class' => $extra_class.' pull-right']
                );
            }
        }

        if (!empty($lis)) {
            $html .= Display::tag('ul', $lis, ['class' => 'breadcrumb']);
        }
    }

    return $html.$additionalBlocks;
}

/**
 * Helper function to get the number of users online, using cache if available.
 *
 * @param int $minutes Number of minutes (how many users were active in those last X minutes?)
 *
 * @return int The number of users currently online
 */
function getOnlineUsersCount($minutes = null)
{
    $number = 0;
    $limit = !empty($minutes) ? intval($minutes) : api_get_setting('time_limit_whosonline');
    $cacheAvailable = api_get_configuration_value('apc');
    if ($cacheAvailable === true) {
        $apcVar = api_get_configuration_value('apc_prefix').'my_campus_whoisonline_count_simple_'.$minutes;
        if (apcu_exists($apcVar)) {
            $number = apcu_fetch($apcVar);
        } else {
            $number = who_is_online_count($limit);
            apcu_store($apcVar, $number, 15);
        }
    } else {
        $number = who_is_online_count($limit);
    }

    return $number;
}

/**
 * Helper function to get the number of users online in a course, using cache if available.
 *
 * @param int   $userId     The user ID
 * @param array $courseInfo The course details
 *
 * @return int The number of users currently online
 */
function getOnlineUsersInCourseCount($userId, $courseInfo)
{
    $cacheAvailable = api_get_configuration_value('apc');
    $numberOnlineInCourse = 0;
    if (!empty($courseInfo['id'])) {
        if ($cacheAvailable === true) {
            $apcVar = api_get_configuration_value('apc_prefix').'my_campus_whoisonline_count_simple_'.$courseInfo['id'];
            if (apcu_exists($apcVar)) {
                $numberOnlineInCourse = apcu_fetch($apcVar);
            } else {
                $numberOnlineInCourse = who_is_online_in_this_course_count(
                    $userId,
                    api_get_setting('time_limit_whosonline'),
                    $courseInfo['id']
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
                $courseInfo['id']
            );
        }
    }

    return $numberOnlineInCourse;
}

/**
 * Helper function to get the number of users online in a session, using cache if available.
 *
 * @param int $sessionId The session ID
 *
 * @return int The number of users currently online
 */
function getOnlineUsersInSessionCount($sessionId)
{
    $cacheAvailable = api_get_configuration_value('apc');

    if (!$sessionId) {
        return 0;
    }

    if ($cacheAvailable === true) {
        $apcVar = api_get_configuration_value('apc_prefix').'my_campus_whoisonline_session_count_simple_'.$sessionId;

        if (apcu_exists($apcVar)) {
            return apcu_fetch($apcVar);
        }

        $numberOnlineInCourse = whoIsOnlineInThisSessionCount(
            api_get_setting('time_limit_whosonline'),
            $sessionId
        );
        apcu_store($apcVar, $numberOnlineInCourse, 15);

        return $numberOnlineInCourse;
    }

    return whoIsOnlineInThisSessionCount(
        api_get_setting('time_limit_whosonline'),
        $sessionId
    );
}
