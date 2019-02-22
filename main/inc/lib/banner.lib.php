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
 * This function returns the custom tabs.
 *
 * @return array
 */
function getCustomTabs()
{
    $urlId = api_get_current_access_url_id();
    $tableSettingsCurrent = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $sql = "SELECT * FROM $tableSettingsCurrent
            WHERE 
                variable = 'show_tabs' AND
                subkey LIKE 'custom_tab_%' AND access_url = $urlId ";
    $result = Database::query($sql);
    $customTabs = [];
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
 * @return bool
 */
function accessToWhoIsOnline()
{
    $user_id = api_get_user_id();
    $course_id = api_get_course_int_id();
    $access = false;
    if ((api_get_setting('showonline', 'world') === 'true' && !$user_id) ||
        (api_get_setting('showonline', 'users') === 'true' && $user_id) ||
        (api_get_setting('showonline', 'course') === 'true' && $user_id && $course_id)
    ) {
        $access = true;
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
    $courseInfo = api_get_course_info();
    $user_id = api_get_user_id();
    $sessionId = api_get_session_id();
    $html = '';

    if (accessToWhoIsOnline()) {
        $number = getOnlineUsersCount();
        $number_online_in_course = getOnlineUsersInCourseCount($user_id, $courseInfo);

        // Display the who's online of the platform
        if ($number &&
            (api_get_setting('showonline', 'world') == 'true' && !$user_id) ||
            (api_get_setting('showonline', 'users') == 'true' && $user_id)
        ) {
            $html .= '<li class="user-online"><a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_self" title="'
                .get_lang('UsersOnline').'" >'
                .Display::return_icon('user.png', get_lang('UsersOnline'), [], ICON_SIZE_TINY)
                .' '.$number.'</a></li>';
        }

        // Display the who's online for the course
        if ($number_online_in_course &&
            (
                is_array($courseInfo) &&
                api_get_setting('showonline', 'course') == 'true' && isset($courseInfo['sysCode'])
            )
        ) {
            $html .= '<li class="user-online-course"><a href="'.api_get_path(WEB_PATH).'whoisonline.php?cidReq='.$courseInfo['sysCode']
                .'" target="_self">'
                .Display::return_icon('course.png', get_lang('UsersOnline').' '.get_lang('InThisCourse'), [], ICON_SIZE_TINY)
                .' '.$number_online_in_course.' </a></li>';
        }

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

    $tabs = api_get_setting('show_tabs');

    // Campus Homepage
    if (in_array('campus_homepage', $tabs)) {
        $navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    } else {
        $menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
    }

    if (api_get_setting('course_catalog_published') == 'true' && api_is_anonymous()) {
        $navigation[SECTION_CATALOG] = $possible_tabs[SECTION_CATALOG];
    }

    if (api_get_user_id() && !api_is_anonymous()) {
        // My Courses
        if (in_array('my_courses', $tabs)) {
            $navigation['mycourses'] = $possible_tabs['mycourses'];
        } else {
            $menu_navigation['mycourses'] = $possible_tabs['mycourses'];
        }

        // My Profile
        if (in_array('my_profile', $tabs) &&
            api_get_setting('allow_social_tool') != 'true'
        ) {
            $navigation['myprofile'] = $possible_tabs['myprofile'];
        } else {
            $menu_navigation['myprofile'] = $possible_tabs['myprofile'];
        }

        // My Agenda
        if (in_array('my_agenda', $tabs)) {
            $navigation['myagenda'] = $possible_tabs['myagenda'];
        } else {
            $menu_navigation['myagenda'] = $possible_tabs['myagenda'];
        }

        // Gradebook
        if (api_get_setting('gradebook_enable') == 'true') {
            if (in_array('my_gradebook', $tabs)) {
                $navigation['mygradebook'] = $possible_tabs['mygradebook'];
            } else {
                $menu_navigation['mygradebook'] = $possible_tabs['mygradebook'];
            }
        }

        // Reporting
        if (in_array('reporting', $tabs)) {
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
        if (in_array('social', $tabs)) {
            if (api_get_setting('allow_social_tool') == 'true') {
                $navigation['social'] = isset($possible_tabs['social']) ? $possible_tabs['social'] : null;
            }
        } else {
            $menu_navigation['social'] = isset($possible_tabs['social']) ? $possible_tabs['social'] : null;
        }

        // Dashboard
        if (in_array('dashboard', $tabs)) {
            if (api_is_platform_admin() || api_is_drh() || api_is_session_admin()) {
                $navigation['dashboard'] = isset($possible_tabs['dashboard']) ? $possible_tabs['dashboard'] : null;
            }
        } else {
            $menu_navigation['dashboard'] = isset($possible_tabs['dashboard']) ? $possible_tabs['dashboard'] : null;
        }

        ///if (api_is_student()) {
        if (true) {
            $params = ['variable = ? AND subkey = ?' => ['status', 'studentfollowup']];
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
            if (in_array('platform_administration', $tabs)) {
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
            $sessionName = $session ? ' ('.cut($session->getName(), MAX_LENGTH_BREADCRUMB).')' : '';
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
            $html .= Display::tag('div', $view_as_student_link, ['id' => 'view_as_link', 'class' => 'float-right']);
        }

        if (!empty($navigation_right)) {
            foreach ($navigation_right as $item) {
                $extra_class = isset($item['class']) ? $item['class'] : null;
                $lis .= Display::tag(
                    'li',
                    $item['title'],
                    ['class' => $extra_class.' float-right']
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
 * @return int The number of users currently online
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
