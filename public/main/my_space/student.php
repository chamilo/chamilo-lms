<?php

/* For licensing terms, see /license.txt */

/**
 * Report on students subscribed to courses I am teaching.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() ||
    api_is_student_boss();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$nameTools = get_lang('Learners');

$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
$active = isset($_GET['active']) ? (int) $_GET['active'] : 1;
$sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;
$this_section = SECTION_TRACKING;

$webCodePath = api_get_path(WEB_CODE_PATH);

$interbreadcrumb[] = [
    'url' => api_is_student_boss() ? '#' : 'index.php',
    'name' => get_lang('Reporting'),
];

if (isset($_GET['user_id']) && '' != $_GET['user_id'] && !isset($_GET['type'])) {
    $interbreadcrumb[] = [
        'url' => 'teachers.php',
        'name' => get_lang('Teachers'),
    ];
}

if (isset($_GET['user_id']) && '' != $_GET['user_id'] && isset($_GET['type']) && 'coach' === $_GET['type']) {
    $interbreadcrumb[] = [
        'url' => 'coaches.php',
        'name' => get_lang('Coaches'),
    ];
}

/**
 * Get total number of users for the current filters.
 */
function get_count_users()
{
    $sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;
    $active = isset($_GET['active']) ? (int) $_GET['active'] : 1;
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }

    return SessionManager::getCountUserTracking(
        $keyword,
        $active,
        $lastConnectionDate,
        null,
        null,
        api_is_student_boss() ? null : STUDENT
    );
}

/**
 * Get paginated list of users for the current filters.
 */
function get_users($from, $limit, $column, $direction)
{
    global $export_csv;
    $active = isset($_GET['active']) ? $_GET['active'] : 1;
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;
    $sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

    $webCodePath = api_get_path(WEB_CODE_PATH);

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }

    $is_western_name_order = api_is_western_name_order();
    $coach_id = api_get_user_id();
    $column = 'u.user_id';
    $drhLoaded = false;
    $students = [];

    // DRH can optionally load all users from all sessions depending on settings.
    if (api_is_drh()) {
        if (api_drh_can_access_all_session_content()) {
            $students = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
                'drh_all',
                api_get_user_id(),
                false,
                $from,
                $limit,
                $column,
                $direction,
                $keyword,
                $active,
                $lastConnectionDate,
                null,
                null,
                api_is_student_boss() ? null : STUDENT
            );
            $drhLoaded = true;
        }
    }

    $checkSessionVisibility = ('true' === api_get_setting('session.show_users_in_active_sessions_in_tracking'));
    if (false === $drhLoaded) {
        $students = UserManager::getUsersFollowedByUser(
            api_get_user_id(),
            api_is_student_boss() ? null : STUDENT,
            false,
            false,
            false,
            $from,
            $limit,
            $column,
            $direction,
            $active,
            $lastConnectionDate,
            api_is_student_boss() ? STUDENT_BOSS : COURSEMANAGER,
            $keyword,
            $checkSessionVisibility
        );
    }

    $url = $webCodePath.'my_space/myStudents.php';

    $all_datas = [];
    foreach ($students as $student_data) {
        $student_id = $student_data['user_id'];
        $student_data = api_get_user_info($student_id);

        $courses = [];
        if (isset($_GET['id_session'])) {
            $courses = Tracking::get_course_list_in_session_from_student($student_id, $sessionId);
        }

        $avg_time_spent = 0;
        $avg_student_score = 0;
        $avg_student_progress = 0;
        $nb_courses_student = 0;

        if (!empty($courses)) {
            foreach ($courses as $course_code) {
                $courseInfo = api_get_course_info($course_code);
                $courseId = $courseInfo['real_id'];

                if (CourseManager::is_user_subscribed_in_course($student_id, $course_code, true)) {
                    $avg_time_spent += Tracking::get_time_spent_on_the_course(
                        $student_id,
                        $courseId,
                        $_GET['id_session']
                    );

                    $my_average = Tracking::get_avg_student_score($student_id, $course_code);
                    if (is_numeric($my_average)) {
                        $avg_student_score += $my_average;
                    }

                    $avg_student_progress += Tracking::get_avg_student_progress($student_id, $course_code);
                    $nb_courses_student++;
                }
            }
        }

        if ($nb_courses_student > 0) {
            $avg_time_spent /= $nb_courses_student;
            $avg_student_score /= $nb_courses_student;
            $avg_student_progress /= $nb_courses_student;
        } else {
            $avg_time_spent = null;
            $avg_student_score = null;
            $avg_student_progress = null;
        }

        $urlDetails = $url."?student=$student_id";
        if (isset($_GET['id_coach']) && 0 != intval($_GET['id_coach'])) {
            $urlDetails = $url."?student=$student_id&id_coach=$coach_id&id_session=$sessionId";
        }

        $row = [];
        if ($is_western_name_order) {
            $first = Display::url($student_data['firstname'], $urlDetails);
            $last = Display::url($student_data['lastname'], $urlDetails);
        } else {
            $first = Display::url($student_data['lastname'], $urlDetails);
            $last = Display::url($student_data['firstname'], $urlDetails);
        }

        if ($export_csv) {
            $row[] = strip_tags($first);
            $row[] = strip_tags($last);
        } else {
            $row[] = $first;
            $row[] = $last;
        }

        $string_date = Tracking::get_last_connection_date($student_id, true);
        $first_date = Tracking::get_first_connection_date($student_id);
        $row[] = $first_date;
        $row[] = $string_date;

        $detailsLink = Display::url(
            Display::getMdiIcon(
                ActionIcon::VIEW_DETAILS,
                'ch-tool-icon',
                null,
                ICON_SIZE_SMALL,
                get_lang('Details').' '.$student_data['username']
            ),
            $urlDetails,
            ['id' => 'details_'.$student_data['username']]
        );

        $lostPasswordLink = '';
        if (api_is_drh() || api_is_platform_admin()) {
            $lostPasswordLink = '&nbsp;'.Display::url(
                    Display::getMdiIcon(
                        ActionIcon::EDIT,
                        'ch-tool-icon',
                        null,
                        ICON_SIZE_SMALL,
                        get_lang('Edit')
                    ),
                    $webCodePath.'my_space/user_edit.php?user_id='.$student_id
                );
        }

        $row[] = $lostPasswordLink.$detailsLink;
        $all_datas[] = $row;
    }

    return $all_datas;
}

if ($export_csv) {
    $is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
} else {
    $is_western_name_order = api_is_western_name_order();
}

$sort_by_first_name = api_sort_by_first_name();

// ---------------------------------------------------------------------
// Build toolbar actions (left and right) for the student list page
// ---------------------------------------------------------------------
$actionsLeft = '';

// 0) "View my progress" (for tracking roles).
$actionsLeft .= Display::url(
    Display::getMdiIcon(
        'chart-box',
        'ch-tool-icon',
        null,
        32,
        get_lang('View my progress')
    ),
    $webCodePath.'auth/my_progress.php'
);

// 1) Main MySpace navigation with "Learners" as the current section.
$actionsLeft .= Display::mySpaceMenu('students');

// 2) Extra actions for DRH and student bosses: skills report.
if (api_is_drh() || api_is_student_boss()) {
    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'badge-account-horizontal',
            'ch-tool-icon',
            null,
            32,
            get_lang('Skills')
        ),
        $webCodePath.'social/my_skills_report.php'
    );
}

// 3) Extra actions only for student bosses: corporate report, schedule, certificates.
if (api_is_student_boss()) {
    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'chart-box',
            'ch-tool-icon',
            null,
            32,
            get_lang('Corporate report')
        ),
        $webCodePath.'my_space/company_reports.php'
    );

    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'calendar-clock',
            'ch-tool-icon',
            null,
            32,
            get_lang('My students schedule')
        ),
        $webCodePath.'my_space/calendar_plan.php'
    );

    $actionsLeft .= Display::url(
        Display::getMdiIcon(
            'certificate',
            'ch-tool-icon',
            null,
            32,
            get_lang('See list of learner certificates')
        ),
        $webCodePath.'gradebook/certificate_report.php'
    );
}

// Right side: print + CSV export.
$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

$actionsRight .= Display::url(
    Display::getMdiIcon(
        ActionIcon::EXPORT_CSV,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('CSV export')
    ),
    api_get_self().'?export=csv&keyword='.$keyword
);

$toolbar = Display::toolbarAction('toolbar-student', [$actionsLeft, $actionsRight]);

$itemPerPage = 10;
$perPage = api_get_setting('profile.my_space_users_items_per_page');
if ($perPage) {
    $itemPerPage = (int) $perPage;
}

$table = new SortableTable(
    'tracking_student',
    'get_count_users',
    'get_users',
    ($is_western_name_order xor $sort_by_first_name) ? 1 : 0,
    $itemPerPage
);

$params = [
    'keyword' => $keyword,
    'active' => $active,
    'sleeping_days' => $sleepingDays,
];
$table->set_additional_parameters($params);

if ($is_western_name_order) {
    $table->set_header(0, get_lang('First name'), false);
    $table->set_header(1, get_lang('Last name'), false);
} else {
    $table->set_header(0, get_lang('Last name'), false);
    $table->set_header(1, get_lang('First name'), false);
}

$table->set_header(2, get_lang('First connection'), false);
$table->set_header(3, get_lang('Latest login'), false);
$table->set_header(4, get_lang('Details'), false);

if ($export_csv) {
    if ($is_western_name_order) {
        $csv_header[] = [
            get_lang('First name'),
            get_lang('Last name'),
            get_lang('First connection'),
            get_lang('Latest login'),
        ];
    } else {
        $csv_header[] = [
            get_lang('Last name'),
            get_lang('First name'),
            get_lang('First connection'),
            get_lang('Latest login'),
        ];
    }
}

$form = new FormValidator(
    'search_user',
    'get',
    $webCodePath.'my_space/student.php'
);
$form = Tracking::setUserSearchForm($form);
$form->setDefaults($params);

if ($export_csv) {
    // Send the CSV file if requested.
    $content = $table->get_table_data();
    foreach ($content as &$row) {
        unset($row[4]);
    }

    $csv_content = array_merge($csv_header, $content);
    ob_end_clean();
    Export::arrayToCsv($csv_content, 'reporting_student_list');
    exit;
} else {
    Display::display_header($nameTools);

    echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

    echo '      <div class="flex flex-wrap gap-2">';
    echo            $toolbar;
    echo '      </div>';

    // Header + toolbar stacked, icons aligned to the left
    echo '  <div class="flex flex-col gap-3 md:gap-4">';
    echo '      <div class="space-y-1">';
    echo            Display::page_subheader($nameTools);

    if (isset($active)) {
        if ($active) {
            $activeLabel = get_lang('Users with an active account');
        } else {
            $activeLabel = get_lang('Users who\'s account has been disabled');
        }
        echo '  <div class="mt-1">';
        echo        Display::page_subheader2($activeLabel);
        echo '  </div>';
    }

    echo '      </div>';
    echo '  </div>';

    echo '  <section class="reporting-students-card bg-white rounded-xl shadow-sm w-full">';
    echo '      <div class="p-4 md:p-5">';
    $form->display();
    echo '      </div>';
    echo '  </section>';

    echo '  <section class="reporting-students-card bg-white rounded-xl shadow-sm w-full">';
    echo '      <div class="overflow-x-auto">';
    $table->display();
    echo '      </div>';
    echo '  </section>';

    echo '</div>';

    Display::display_footer();
}
