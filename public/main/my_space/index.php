<?php
/* For licensing terms, see /license.txt */

/**
 * Homepage for the MySpace directory.
 */

// Resetting the course id.
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$this_section = SECTION_TRACKING;

ob_start();
$nameTools = get_lang('Reporting');
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];
$display = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;
if (empty($display) && api_is_platform_admin()) {
    $display = 'overview';
}
$csv_content = [];
$user_id = api_get_user_id();
$session_id = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$is_coach = api_is_coach($session_id);
$is_platform_admin = api_is_platform_admin();
$is_drh = api_is_drh();
$is_session_admin = api_is_session_admin();
$skipData = ('true' === api_get_setting('tracking.tracking_skip_generic_data'));

$logInfo = [
    'tool' => SECTION_TRACKING,
];
Event::registerLog($logInfo);

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();
if (!$allowToTrack) {
    api_not_allowed(true);
}

if ($is_session_admin) {
    header('location:session.php');
    exit;
}

// ---------------------------------------------------------------------
// View selector (admin / teacher / coach / drh)
// ---------------------------------------------------------------------
$views = ['admin', 'teacher', 'coach', 'drh'];
$view = 'teacher';
if (isset($_GET['view']) && in_array($_GET['view'], $views, true)) {
    $view = $_GET['view'];
}

// ---------------------------------------------------------------------
// Build toolbar actions (left and right) for MySpace homepage
// ---------------------------------------------------------------------
$actionsRight = '';
$actionsLeft = '';

if (in_array($display, ['useroverview', 'sessionoverview', 'courseoverview'], true)) {
    // CSV export icon for overview pages.
    $actionsRight .= Display::url(
        Display::getMdiIcon(
            'file-delimited-outline',
            'ch-tool-icon',
            null,
            32,
            get_lang('CSV export')
        ),
        api_get_self().'?display='.$display.'&export=csv&view='.$view
    );
}

// Print icon (always available).
$actionsRight .= Display::url(
    Display::getMdiIcon(
        'printer',
        'ch-tool-icon',
        null,
        32,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print()']
);

if (!empty($session_id) &&
    !in_array(
        $display,
        ['accessoverview', 'lpprogressoverview', 'progressoverview', 'exerciseprogress', 'surveyoverview'],
        true
    )
) {
    // Session context: show back button and "import users" action,
    // keeping previous behavior.
    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'arrow-left-bold-box',
            'ch-tool-icon',
            null,
            32,
            get_lang('Back')
        ),
        'index.php'
    );

    if (!api_is_platform_admin()) {
        if ('true' === api_get_setting('add_users_by_coach') && $is_coach) {
            $actionsLeft .= Display::url(
                Display::getMdiIcon(
                    'archive-arrow-up',
                    'ch-tool-icon',
                    null,
                    32,
                    get_lang('Import list of users')
                ),
                'user_import.php?id_session='.$session_id.'&action=export&amp;type=xml'
            );
        }
    } else {
        $actionsLeft .= Display::url(
            Display::getMdiIcon(
                'archive-arrow-up',
                'ch-tool-icon',
                null,
                32,
                get_lang('Import list of users')
            ),
            'user_import.php?id_session='.$session_id.'&action=export&amp;type=xml'
        );
    }
} else {
    // No session context or a "global" display:
    // 0) always show "View my progress" first.
    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'chart-box',
            'ch-tool-icon',
            null,
            32,
            get_lang('View my progress')
        ),
        api_get_path(WEB_CODE_PATH).'auth/my_progress.php'
    );

    // 1) Show main MySpace navigation.
    $actionsLeft .= Display::mySpaceMenu($display);

    // 2) Extra actions: "View my progress", calendar plugin, certificates.

    // Optional Learning Calendar plugin entry (teachers only).
    $pluginCalendar = 'true' === api_get_plugin_setting('learning_calendar', 'enabled');
    if ($pluginCalendar && api_is_teacher()) {
        $lpCalendar = \LearningCalendarPlugin::create();
        $actionsLeft .= Display::url(
            Display::getMdiIcon(
                'calendar-text',
                'ch-tool-icon',
                null,
                32,
                $lpCalendar->get_lang('Learning calendar')
            ),
            api_get_path(WEB_PLUGIN_PATH).'LearningCalendar/start.php'
        );
    }
}

// ---------------------------------------------------------------------
// Global stats for MySpace dashboard
// ---------------------------------------------------------------------
$userId = api_get_user_id();
$stats = Tracking::getStats($userId, true);

$numberStudents = $stats['student_count'];
$students = $stats['student_list'];
$numberStudentBosses = $stats['student_bosses'];
$numberTeachers = $stats['teachers'];
$countHumanResourcesUsers = $stats['drh'];
$countAssignedCourses = $stats['assigned_courses'];
$countCourses = $stats['courses'];
$sessions = $stats['session_list'];

$sessionIdList = [];
if (!empty($sessions)) {
    foreach ($sessions as $session) {
        $sessionIdList[] = $session['id'];
    }
}

// Sessions for the user.
$countSessions = count($sessions);
$total_time_spent = 0;
$total_courses = 0;
$avgTotalProgress = 0;
$nb_inactive_students = 0;
$numberAssignments = 0;
$inactiveTime = time() - (3600 * 24 * 7);
$daysAgo = 7;
$studentIds = [];
$avg_courses_per_student = 0;

$view = new Template($nameTools);
$view->assign('students', $numberStudents);
$view->assign('studentbosses', $numberStudentBosses);
$view->assign('numberTeachers', $numberTeachers);
$view->assign('humanresources', $countHumanResourcesUsers);
$view->assign(
    'total_user',
    $numberStudents + $numberStudentBosses + $numberTeachers + $countHumanResourcesUsers
);
$view->assign('studentboss', STUDENT_BOSS);
$view->assign('drh', DRH);
$view->assign('stats', $stats);

$form = new FormValidator(
    'search_user',
    'get',
    api_get_path(WEB_CODE_PATH).'my_space/student.php'
);
$form = Tracking::setUserSearchForm($form);

$totalTimeSpent = null;
$averageScore = null;
$posts = null;

if (false === $skipData) {
    if (!empty($students)) {
        // Students.
        $studentIds = array_values($students);
        $progress = 0; // @todo: fix stats (Tracking::get_avg_student_progress($studentIds)).
        $countAssignments = 0; // @todo: restore assignments count when stats are fixed.

        // Average progress.
        if ($numberStudents > 0) {
            $avgTotalProgress = $progress / $numberStudents;
            $numberAssignments = $countAssignments / $numberStudents;
            $avg_courses_per_student = $countCourses / $numberStudents;
        }

        $totalTimeSpent = Tracking::get_time_spent_on_the_platform($studentIds);
        $posts = 0; // @todo: restore forum posts stats.
        $averageScore = Tracking::getAverageStudentScore($studentIds);
    }

    if ($export_csv) {
        // CSV export.
        $csv_content[] = [get_lang('Learners')];
        $csv_content[] = [get_lang('Inactive learners'), $nb_inactive_students];
        $csv_content[] = [get_lang('Time spent on portal'), $totalTimeSpent];
        $csv_content[] = [
            get_lang('Average number of courses to which my learners are subscribed'),
            round($avg_courses_per_student, 3),
        ];
        $csv_content[] = [
            get_lang('Progress in courses'),
            is_null($avgTotalProgress)
                ? null
                : round($avgTotalProgress, 2).'%',
        ];
        $csv_content[] = [
            get_lang('Tests score'),
            is_null($averageScore)
                ? null
                : round($averageScore, 2).'%',
        ];
        $csv_content[] = [get_lang('Posts in forum'), $posts];
        $csv_content[] = [get_lang('Average assignments per learner'), $numberAssignments];
        $csv_content[] = [];
    } else {
        $lastConnectionDate = api_get_utc_datetime(strtotime('15 days ago'));
        $countActiveUsers = SessionManager::getCountUserTracking(
            null,
            1,
            null,
            [],
            []
        );
        $countSleepingTeachers = SessionManager::getTeacherTracking(
            api_get_user_id(),
            1,
            $lastConnectionDate,
            true,
            $sessionIdList
        );

        $countSleepingStudents = SessionManager::getCountUserTracking(
            null,
            1,
            $lastConnectionDate,
            $sessionIdList,
            $studentIds
        );

        $report['AverageCoursePerStudent'] = is_null($avg_courses_per_student)
            ? ''
            : round($avg_courses_per_student, 3);
        $report['InactivesStudents'] = $nb_inactive_students;
        $report['AverageTimeSpentOnThePlatform'] = is_null($totalTimeSpent)
            ? '00:00:00'
            : api_time_to_hms($totalTimeSpent);
        $report['AverageProgressInLearnpath'] = is_null($avgTotalProgress)
            ? ''
            : round($avgTotalProgress, 2).'%';
        $report['AvgCourseScore'] = is_null($averageScore) ? '0' : round($averageScore, 2).'%';
        $report['AveragePostsInForum'] = is_null($posts) ? '0' : round($posts, 2);
        $report['AverageAssignments'] = is_null($numberAssignments) ? '' : round($numberAssignments, 2);

        $view->assign('report', $report);
    }
}

$view->assign('header', $nameTools);
$view->assign('form', $form->returnForm());
$view->assign('actions', Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]));
$view->assign('title', get_lang('Learners').' ('.$numberStudents.')');

$template = $view->get_template('my_space/index.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();

// Send the CSV file if requested.
if ($export_csv) {
    ob_end_clean();
    Export::arrayToCsv($csv_content, 'reporting_index');
    exit;
}
