<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Framework\Container;

ob_start();
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : null;

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$session = api_get_session_entity($sessionId);

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];

if (isset($_GET['id_session']) && '' != $_GET['id_session']) {
    $interbreadcrumb[] = ['url' => 'session.php', 'name' => get_lang('Course sessions')];
}

if (isset($_GET['user_id']) && '' != $_GET['user_id'] && isset($_GET['type']) && 'coach' == $_GET['type']) {
    $interbreadcrumb[] = ['url' => 'coaches.php', 'name' => get_lang('Coaches')];
}

if (isset($_GET['user_id']) && '' != $_GET['user_id'] && isset($_GET['type']) && 'student' == $_GET['type']) {
    $interbreadcrumb[] = ['url' => 'student.php', 'name' => get_lang('Learners')];
}

if (isset($_GET['user_id']) && '' != $_GET['user_id'] && !isset($_GET['type'])) {
    $interbreadcrumb[] = ['url' => 'teachers.php', 'name' => get_lang('Teachers')];
}

/**
 * Legacy helper kept for backwards-compatibility.
 */
function count_courses()
{
    global $nb_courses;

    return $nb_courses;
}

// Page header / toolbar helpers.
$headerTitle = get_lang('Courses');
$pageTitle = $headerTitle;
$toolbar = '';

// Checking if the current coach is the admin coach.
$showImportIcon = false;
if ('true' == api_get_setting('add_users_by_coach')) {
    if (!api_is_platform_admin()) {
        if ($session && $session->hasUserAsGeneralCoach(api_get_user_entity())) {
            $showImportIcon = true;
        }
    }
}

$user_id = 0;
$a_courses = [];
$menu_items = [];

if (api_is_platform_admin(true, true)) {
    $title = '';

    if (empty($sessionId)) {
        if (isset($_GET['user_id'])) {
            $user_id = (int) $_GET['user_id'];
            $user_info = api_get_user_info($user_id);
            $title = get_lang('Courses assigned to').' '.api_get_person_name(
                    $user_info['firstname'],
                    $user_info['lastname']
                );
            $courses = CourseManager::get_course_list_of_user_as_course_admin($user_id);
        } else {
            $title = get_lang('Your courses');
            $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        }
    } else {
        $session_name = api_get_session_name($sessionId);
        $title = $session_name.' : '.get_lang('Courses in this session');
        $courses = Tracking::get_courses_list_from_session($sessionId);
    }

    $a_courses = array_keys($courses);

    if (!api_is_session_admin()) {
        // First icon: go back to "Follow up" (My space home)

        // Keep existing navigation icons
        $menu_items[] = Display::url(
            Display::getMdiIcon(
                ToolIcon::TRACKING,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('View my progress')
            ),
            api_get_path(WEB_CODE_PATH).'auth/my_progress.php'
        );
        $menu_items[] = Display::url(
            Display::getMdiIcon(
                ToolIcon::COURSE_PROGRESS,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Global view')
            ),
            api_get_path(WEB_CODE_PATH).'my_space/index.php'
        );
        $menu_items[] = Display::url(
            Display::getMdiIcon(
                ObjectIcon::USER,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Learners')
            ),
            'index.php?view=drh_students&display=yourstudents'
        );
        $menu_items[] = Display::url(
            Display::getMdiIcon(
                ObjectIcon::TEACHER,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Teachers')
            ),
            'teachers.php'
        );
        $menu_items[] = Display::url(
            Display::getMdiIcon(
                ObjectIcon::COURSE,
                'ch-tool-icon-disabled',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Courses')
            ),
            '#'
        );
        $menu_items[] = Display::url(
            Display::getMdiIcon(
                ObjectIcon::SESSION,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Sessions')
            ),
            api_get_path(WEB_CODE_PATH).'my_space/session.php'
        );

        // Question stats tabs (pill-style buttons)
        $currentScript = basename($_SERVER['SCRIPT_NAME']);
        $isGlobal = 'question_stats_global.php' === $currentScript;
        $isDetail = 'question_stats_global_detail.php' === $currentScript;

        $questionTabs =
            '<div class="inline-flex items-center ml-4">'.
            '<div class="inline-flex rounded-full bg-gray-10 border border-gray-25 px-1 py-1 text-body-2">'.
            '<a href="'.api_get_path(WEB_CODE_PATH).'my_space/question_stats_global.php"'
            .' class="px-3 py-1 rounded-full transition '
            .($isGlobal
                ? 'bg-white text-gray-90 shadow-sm'
                : 'text-gray-50 hover:bg-gray-15 hover:text-gray-90').'"'
            .'>'.get_lang('Question stats').'</a>'.
            '<a href="'.api_get_path(WEB_CODE_PATH).'my_space/question_stats_global_detail.php"'
            .' class="ml-1 px-3 py-1 rounded-full transition '
            .($isDetail
                ? 'bg-white text-gray-90 shadow-sm'
                : 'text-gray-50 hover:bg-gray-15 hover:text-gray-90').'"'
            .'>'.get_lang('Detailed questions stats').'</a>'.
            '</div>'.
            '</div>';

        $menu_items[] = $questionTabs;

        if (api_can_login_as($user_id)) {
            $menu_items[] = '<a href="'.
                api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$user_id.
                '&sec_token='.Security::get_existing_token().'">'.
                Display::getMdiIcon(
                    ActionIcon::LOGIN_AS,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_MEDIUM,
                    get_lang('Login as')
                ).
                '</a>&nbsp;&nbsp;';
        }
    }

    $actionsLeft = '';
    $actionsRight = '';

    if (count($menu_items) > 1) {
        foreach ($menu_items as $item) {
            $actionsLeft .= $item;
        }
    }

    if (count($a_courses) > 0) {
        $actionsRight .= Display::url(
            Display::getMdiIcon(ActionIcon::PRINT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Print')),
            'javascript: void(0);',
            ['onclick' => 'javascript: window.print();']
        );
    }

    $toolbar = Display::toolbarAction('toolbar-course', [$actionsLeft, $actionsRight]);
    $pageTitle = $title;
}

// Optional import link for general coaches.
$importLink = '';
if ($showImportIcon) {
    $importLink = '<a href="user_import.php?id_session='.$sessionId.'&action=export&type=xml">'.
        Display::getMdiIcon(
            ActionIcon::IMPORT_ARCHIVE,
            'ch-tool-icon',
            null,
            ICON_SIZE_SMALL,
            get_lang('Import users list')
        ).
        '&nbsp;'.
        get_lang('Import users list').
        '</a>';
}

/**
 * @return int
 */
function get_count_courses()
{
    $userId = api_get_user_id();
    $session = api_get_session_entity($_GET['sid'] ?? 0);
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
    $drhLoaded = false;

    if (api_is_drh()) {
        if (api_drh_can_access_all_session_content()) {
            if (null === $session) {
                $count = SessionManager::getAllCoursesFollowedByUser(
                    $userId,
                    null,
                    null,
                    null,
                    null,
                    null,
                    true,
                    $keyword
                );
            } else {
                $count = SessionManager::getCourseCountBySessionId(
                    $session->getId(),
                    $keyword
                );
            }
            $drhLoaded = true;
        }
    }

    if (false == $drhLoaded) {
        if ($session && $session->hasUserAsGeneralCoach(api_get_user_entity())) {
            $courseList = SessionManager::getCoursesInSession($session->getId());
            $count = count($courseList);
        } else {
            $count = CourseManager::getCoursesFollowedByUser(
                $userId,
                COURSEMANAGER,
                null,
                null,
                null,
                null,
                true,
                $keyword,
                (int) $session?->getId()
            );
        }
    }

    return $count;
}

/**
 * @param int    $from
 * @param int    $limit
 * @param string $column
 * @param string $direction
 *
 * @return array
 */
function get_courses($from, $limit, $column, $direction)
{
    $userId = api_get_user_id();
    $sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : 0;
    $session = api_get_session_entity($sessionId);
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
    $follow = isset($_GET['follow']) ? true : false;
    $drhLoaded = false;

    if (api_is_drh()) {
        if (api_drh_can_access_all_session_content()) {
            $courses = SessionManager::getAllCoursesFollowedByUser(
                $userId,
                $sessionId,
                $from,
                $limit,
                $column,
                $direction,
                false,
                $keyword
            );
            $drhLoaded = true;
        }
    }

    if (false == $drhLoaded) {
        // General coach can see all reports.
        if ($session && $session->hasUserAsGeneralCoach(api_get_user_entity())) {
            $courseList = SessionManager::getCoursesInSession($session->getId());
            $courses = [];
            if (!empty($courseList)) {
                foreach ($courseList as $courseId) {
                    $courses[] = api_get_course_info_by_id($courseId);
                }
            }
        } else {
            $courses = CourseManager::getCoursesFollowedByUser(
                $userId,
                COURSEMANAGER,
                $from,
                $limit,
                $column,
                $direction,
                false,
                $keyword,
                $sessionId,
                $follow
            );
        }
    }

    $courseList = [];
    if (!empty($courses)) {
        foreach ($courses as $data) {
            $courseCode = $data['code'];
            $courseId = $data['real_id'];
            $courseInfo = api_get_course_info($courseCode);
            $course = api_get_course_entity($courseId);

            if (null === $session) {
                $userList = CourseManager::get_user_list_from_course_code($data['code']);
            } else {
                $userList = CourseManager::get_user_list_from_course_code(
                    $data['code'],
                    $sessionId,
                    null,
                    null,
                    0
                );
            }

            $userIdList = [];
            if (!empty($userList)) {
                foreach ($userList as $user) {
                    $userIdList[] = $user['user_id'];
                }
            }

            $messagesInCourse = 0;
            $assignmentsInCourse = 0;
            $avgTimeSpentInCourse = 0;
            $avgProgressInCourse = 0;
            $countStudents = 0;
            $avgScoreInCourse = 0;

            if (count($userIdList) > 0) {
                $countStudents = count($userIdList);
                // Tracking data.
                $avgProgressInCourse = Tracking::get_avg_student_progress($userIdList, $course, [], $session);
                $avgScoreInCourse = Tracking::get_avg_student_score($userIdList, $course, [], $session);
                $avgTimeSpentInCourse = Tracking::get_time_spent_on_the_course(
                    $userIdList,
                    $courseInfo['real_id'],
                    $sessionId
                );
                $messagesInCourse = Container::getForumPostRepository()->countCourseForumPosts($course, $session);
                $assignmentsInCourse = Container::getStudentPublicationRepository()->countCoursePublications(
                    $course,
                    $session
                );
                $avgTimeSpentInCourse = api_time_to_hms($avgTimeSpentInCourse / $countStudents);
                $avgProgressInCourse = round($avgProgressInCourse / $countStudents, 2);

                if (is_numeric($avgScoreInCourse)) {
                    $avgScoreInCourse = round($avgScoreInCourse / $countStudents, 2).'%';
                }
            }

            $thematic = new Thematic();
            $tematic_advance = $thematic->get_total_average_of_thematic_advances($course, $session);
            $tematicAdvanceProgress = '-';
            if (!empty($tematic_advance)) {
                $tematicAdvanceProgress = '<a
                    title="'.get_lang('Go to thematic advance').'"
                    href="'.api_get_path(WEB_CODE_PATH).'course_progress/index.php?cid='.$courseId.'&sid='.$sessionId.'">'.
                    $tematic_advance.'%</a>';
            }

            $courseIcon = '<a
                href="'.api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?cid='.$courseId.'&sid='.$sessionId.'">
                '.Display::getMdiIcon(
                    ActionIcon::VIEW_DETAILS,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Details')
                ).'
              </a>';

            $title = Display::url(
                $data['title'],
                $courseInfo['course_public_url'].'?id_session='.$sessionId
            );

            $attendanceLink = '';
            if (!empty($sessionId)) {
                $sessionInfo = api_get_session_info($sessionId);
                $startDate = $sessionInfo['access_start_date'];
                $endDate = $sessionInfo['access_end_date'];
                $attendance = new Attendance();
                $checkExport = $attendance->getAttendanceLogin($startDate, $endDate);
                if (false !== $checkExport) {
                    $attendanceLink = Display::url(
                        Display::getMdiIcon(ToolIcon::ATTENDANCE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Logins')),
                        api_get_path(WEB_CODE_PATH).'attendance/index.php?cid='.$courseId.'&sid='.$sessionId.'&action=calendar_logins'
                    );
                }
            }

            $courseList[] = [
                $title,
                $countStudents,
                is_null($avgTimeSpentInCourse) ? '-' : $avgTimeSpentInCourse,
                $tematicAdvanceProgress,
                is_null($avgProgressInCourse) ? '-' : $avgProgressInCourse.'%',
                is_null($avgScoreInCourse) ? '-' : $avgScoreInCourse,
                is_null($messagesInCourse) ? '-' : $messagesInCourse,
                is_null($assignmentsInCourse) ? '-' : $assignmentsInCourse,
                $attendanceLink,
                $courseIcon,
            ];
        }
    }

    return $courseList;
}

$table = new SortableTable(
    'tracking_course',
    'get_count_courses',
    'get_courses',
    1,
    10
);

$table->set_header(0, get_lang('Course title'), false);
$table->set_header(1, get_lang('Learners'), false);
$table->set_header(
    2,
    get_lang('Time spent in the course').
    Display::getMdiIcon(
        ActionIcon::INFORMATION,
        'ch-tool-icon',
        null,
        ICON_SIZE_SMALL,
        get_lang('Time in course')
    ),
    false
);
$table->set_header(3, get_lang('Thematic advance'), false);
$table->set_header(
    4,
    get_lang('Progress').
    Display::getMdiIcon(
        ActionIcon::INFORMATION,
        'ch-tool-icon',
        null,
        ICON_SIZE_SMALL,
        get_lang('Average of all learners in all courses')
    ),
    false
);
$table->set_header(
    5,
    get_lang('Average score in learning paths').
    Display::getMdiIcon(
        ActionIcon::INFORMATION,
        'ch-tool-icon',
        null,
        ICON_SIZE_SMALL,
        get_lang('Average of all learners in all courses')
    ),
    false
);
$table->set_header(6, get_lang('Messages per learner'), false);
$table->set_header(7, get_lang('Assignments'), false);
$table->set_header(8, get_lang('Attendances'), false);
$table->set_header(9, get_lang('Details'), false);

$form = new FormValidator('search_course', 'get', api_get_path(WEB_CODE_PATH).'my_space/course.php');
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'));
$form->addElement('hidden', 'sid', $sessionId);

$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;

$params = [
    'session_id' => $sessionId,
    'keyword' => $keyword,
];
$table->set_additional_parameters($params);

$form->setDefaults($params);

// === Render page layout with unified style ===
Display::display_header($headerTitle);

echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Header row: title on the left, toolbar and optional import link on the right.
echo '  <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">';
echo '      <div class="space-y-1">';
echo            Display::page_subheader($pageTitle);
echo '      </div>';

echo '      <div class="flex flex-col items-end gap-2">';
echo '          <div class="flex justify-start md:justify-end gap-2">';
echo                $toolbar;
echo '          </div>';

if (!empty($importLink)) {
    echo '      <div class="mt-1 text-sm text-right">';
    echo            $importLink;
    echo '      </div>';
}

echo '      </div>';
echo '  </div>';

// Filters card.
echo '  <section class="reporting-students-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '      </div>';
echo '  </section>';

// Table card.
echo '  <section class="reporting-students-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="overflow-x-auto">';
$table->display();
echo '      </div>';
echo '  </section>';

echo '</div>';

Display::display_footer();
