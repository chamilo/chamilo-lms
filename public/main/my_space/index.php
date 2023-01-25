<?php
/* For licensing terms, see /license.txt */

/**
 * Homepage for the MySpace directory.
 */

// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$htmlHeadXtra[] = api_get_jqgrid_js();
//$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_PUBLIC_PATH).'assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js"></script>';
$this_section = SECTION_TRACKING;

ob_start();
$nameTools = get_lang('Reporting');
$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'] ? true : false;
$display = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;
$csv_content = [];
$user_id = api_get_user_id();
$session_id = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$is_coach = api_is_coach($session_id);
$is_platform_admin = api_is_platform_admin();
$is_drh = api_is_drh();
$is_session_admin = api_is_session_admin();
$skipData = api_get_configuration_value('tracking_skip_generic_data');

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

// Get views
$views = ['admin', 'teacher', 'coach', 'drh'];
$view = 'teacher';
if (isset($_GET['view']) && in_array($_GET['view'], $views)) {
    $view = $_GET['view'];
}

$menu_items = [];
$pluginCalendar = 'true' === api_get_plugin_setting('learning_calendar', 'enabled');
$calendarMenuAdded = false;

if ($is_platform_admin) {
    if ('admin' === $view) {
        $menu_items[] = Display::url(
            Display::return_icon('teacher.png', get_lang('Trainer View'), [], ICON_SIZE_MEDIUM),
            api_get_self().'?view=teacher'
        );
        $menu_items[] = Display::url(
            Display::return_icon('star_na.png', get_lang('Admin view'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'my_space/admin_view.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('quiz.png', get_lang('Exam tracking'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/exams.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('statistics.png', get_lang('Current courses report'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'my_space/current_courses.php'
        );
    /*
    $menu_items[] = Display::url(
        Display::return_icon('dashboard.png', get_lang('Dashboard'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'dashboard/index.php'
    );
    */
    } else {
        $menu_items[] = Display::url(
            Display::return_icon(
                'teacher_na.png',
                get_lang('Trainer View'),
                [],
                ICON_SIZE_MEDIUM
            ),
            ''
        );
        $menu_items[] = Display::url(
            Display::return_icon('star.png', get_lang('Admin view'), [], ICON_SIZE_MEDIUM),
            //api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin'
            api_get_path(WEB_CODE_PATH).'my_space/admin_view.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('quiz.png', get_lang('Exam tracking'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'tracking/exams.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('statistics.png', get_lang('Current courses report'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'my_space/current_courses.php'
        );
        /*
        $menu_items[] = Display::url(
            Display::return_icon('dashboard.png', get_lang('Dashboard'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'dashboard/index.php'
        );
        */
        if ($pluginCalendar) {
            $lpCalendar = LearningCalendarPlugin::create();
            $menu_items[] = Display::url(
                Display::return_icon('agenda.png', $lpCalendar->get_lang('Learning calendar'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_PLUGIN_PATH).'learning_calendar/start.php'
            );
            $calendarMenuAdded = true;
        }
    }
}

if ($is_drh) {
    $view = 'drh';
    $menu_items[] = Display::url(
        Display::return_icon('user_na.png', get_lang('Learners'), [], ICON_SIZE_MEDIUM),
        '#'
    );
    $menu_items[] = Display::url(
        Display::return_icon('teacher.png', get_lang('Teachers'), [], ICON_SIZE_MEDIUM),
        'teachers.php'
    );
    $menu_items[] = Display::url(
        Display::return_icon('course.png', get_lang('Courses'), [], ICON_SIZE_MEDIUM),
        'course.php'
    );
    $menu_items[] = Display::url(
        Display::return_icon('session.png', get_lang('Course sessions'), [], ICON_SIZE_MEDIUM),
        'session.php'
    );
    $menu_items[] = Display::url(
        Display::return_icon('empty_evaluation.png', get_lang('Corporate report'), [], ICON_SIZE_MEDIUM),
        'company_reports.php'
    );
    $menu_items[] = Display::url(
        Display::return_icon('evaluation_rate.png', get_lang('Corporate reportResumed'), [], ICON_SIZE_MEDIUM),
        'company_reports_resumed.php'
    );
}

$actionsRight = '';
$actionsLeft = '';
if ('useroverview' === $display || 'sessionoverview' === $display || 'courseoverview' === $display) {
    $actionsRight .= Display::url(
        Display::return_icon(
            'export_csv.png',
            get_lang('CSV export'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_self().'?display='.$display.'&export=csv&view='.$view
    );
}

$actionsRight .= Display::url(
    Display::return_icon(
        'printer.png',
        get_lang('Print'),
        null,
        ICON_SIZE_MEDIUM
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print()']
);

if (!empty($session_id) &&
    !in_array(
        $display,
        ['accessoverview', 'lpprogressoverview', 'progressoverview', 'exerciseprogress', 'surveyoverview']
    )
) {
    $actionsLeft .= Display::url(
        Display::return_icon(
            'back.png',
            get_lang('Back'),
            null,
            ICON_SIZE_MEDIUM
        ),
        'index.php'
    );
    if (!api_is_platform_admin()) {
        if ('true' === api_get_setting('add_users_by_coach')) {
            if ($is_coach) {
                $actionsLeft .= Display::url(
                    Display::return_icon(
                        'excel.png',
                        get_lang('Import list of users'),
                        null,
                        ICON_SIZE_MEDIUM
                    ),
                    'user_import.php?id_session='.$session_id.'&action=export&amp;type=xml'
                );
            }
        }
    } else {
        Display::url(
            Display::return_icon(
                'excel.png',
                get_lang('Import list of users'),
                null,
                ICON_SIZE_MEDIUM
            ),
            'user_import.php?id_session='.$session_id.'&action=export&amp;type=xml'
        );
    }
} else {
    $actionsLeft .= Display::url(
        Display::return_icon(
            'statistics.png',
            get_lang('View my progress'),
            null,
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'auth/my_progress.php'
    );

    if ($pluginCalendar && api_is_teacher() && false === $calendarMenuAdded) {
        $lpCalendar = LearningCalendarPlugin::create();
        $actionsLeft .= Display::url(
            Display::return_icon('agenda.png', $lpCalendar->get_lang('Learning calendar'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_PLUGIN_PATH).'learning_calendar/start.php'
        );
    }

    if (api_is_platform_admin(true) || api_is_student_boss()) {
        $actionsLeft .= Display::url(
            Display::return_icon(
                "certificate_list.png",
                get_lang('GradebookSeeListOfLearnersCertificates'),
                null,
                ICON_SIZE_MEDIUM
            ),
            api_get_path(WEB_CODE_PATH).'gradebook/certificate_report.php'
        );
    }
}

// Actions menu
$nb_menu_items = count($menu_items);
if (empty($session_id) ||
    in_array(
        $display,
        ['accessoverview', 'lpprogressoverview', 'progressoverview', 'exerciseprogress', 'surveyoverview']
    )
) {
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            $actionsLeft .= $item;
        }
    }
}

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

// Sessions for the user
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
        // Students
        $studentIds = array_values($students);
        $progress = 0; //Tracking::get_avg_student_progress($studentIds);
        // @todo fix stats
        //$countAssignments = Tracking::count_student_assignments($studentIds);
        $countAssignments = 0;
        // average progress
        $avgTotalProgress = $progress / $numberStudents;
        // average assignments
        $numberAssignments = $countAssignments / $numberStudents;
        $avg_courses_per_student = $countCourses / $numberStudents;
        $totalTimeSpent = Tracking::get_time_spent_on_the_platform($studentIds);
        //$posts = Tracking::count_student_messages($studentIds);
        $posts = 0;
        $averageScore = Tracking::getAverageStudentScore($studentIds);
    }

    if ($export_csv) {
        //csv part
        $csv_content[] = [get_lang('Learners')];
        $csv_content[] = [get_lang('InactivesLearners'), $nb_inactive_students];
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
$view->assign('title', get_lang('Students').' ('.$numberStudents.')');

$template = $view->get_template('my_space/index.tpl');
$content = $view->fetch($template);
$view->assign('content', $content);
$view->display_one_col_template();

// Send the csv file if asked
if ($export_csv) {
    ob_end_clean();
    Export:: arrayToCsv($csv_content, 'reporting_index');
    exit;
}
