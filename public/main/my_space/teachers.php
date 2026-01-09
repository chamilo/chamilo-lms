<?php

/* For licensing terms, see /license.txt */

/**
 * Teacher report.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$nameTools = get_lang('Teachers');
$this_section = SECTION_TRACKING;

$export_csv = isset($_GET['export']) && 'csv' === $_GET['export'];
$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
$active = isset($_GET['active']) ? (int) $_GET['active'] : 1;
$sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;

$webCodePath = api_get_path(WEB_CODE_PATH);

$interbreadcrumb[] = [
    'url' => 'index.php',
    'name' => get_lang('Reporting'),
];

if (isset($_GET['user_id']) && '' !== $_GET['user_id'] && !isset($_GET['type'])) {
    $interbreadcrumb[] = [
        'url' => 'teachers.php',
        'name' => get_lang('Teachers'),
    ];
}

if (isset($_GET['user_id']) && '' !== $_GET['user_id'] && isset($_GET['type']) && 'coach' === $_GET['type']) {
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
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $active = isset($_GET['active']) ? (int) $_GET['active'] : 1;
    $sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;

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
        COURSEMANAGER
    );
}

/**
 * Get paginated list of users for the current filters.
 */
function get_users($from, $limit, $column, $direction)
{
    $active = isset($_GET['active']) ? $_GET['active'] : 1;
    $keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
    $sleepingDays = isset($_GET['sleeping_days']) ? (int) $_GET['sleeping_days'] : null;
    $sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;

    $lastConnectionDate = null;
    if (!empty($sleepingDays)) {
        $lastConnectionDate = api_get_utc_datetime(strtotime($sleepingDays.' days ago'));
    }

    $is_western_name_order = api_is_western_name_order();
    $coach_id = api_get_user_id();

    $drhLoaded = false;
    $students = [];

    if (api_is_drh() && api_drh_can_access_all_session_content()) {
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
            COURSEMANAGER
        );
        $drhLoaded = true;
    }

    $checkSessionVisibility = ('true' === api_get_setting('session.show_users_in_active_sessions_in_tracking'));

    if (false === $drhLoaded) {
        $students = UserManager::getUsersFollowedByUser(
            api_get_user_id(),
            COURSEMANAGER,
            false,
            false,
            false,
            $from,
            $limit,
            $column,
            $direction,
            $active,
            $lastConnectionDate,
            COURSEMANAGER,
            $keyword,
            $checkSessionVisibility
        );
    }

    $all_datas = [];
    $webCodePath = api_get_path(WEB_CODE_PATH);
    $url = $webCodePath.'my_space/myStudents.php';

    foreach ($students as $student_data) {
        $student_id = $student_data['user_id'];
        $student_data = api_get_user_info($student_id);

        $courses = [];
        if (!empty($sessionId)) {
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
                        $sessionId
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
            $avg_time_spent = $avg_time_spent / $nb_courses_student;
            $avg_student_score = $avg_student_score / $nb_courses_student;
            $avg_student_progress = $avg_student_progress / $nb_courses_student;
        } else {
            $avg_time_spent = null;
            $avg_student_score = null;
            $avg_student_progress = null;
        }

        $urlDetails = $url.'?student='.$student_id.'&origin=teacher_details';

        if (isset($_GET['id_coach']) && 0 !== (int) $_GET['id_coach']) {
            $urlDetails = $url.'?student='.$student_id.'&id_coach='.$coach_id.'&id_session='.$sessionId;
        }

        $row = [];

        if ($is_western_name_order) {
            $row[] = Display::url($student_data['firstname'], $urlDetails);
            $row[] = Display::url($student_data['lastname'], $urlDetails);
        } else {
            $row[] = $student_data['lastname'];
            $row[] = $student_data['firstname'];
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

        $row[] = $detailsLink;
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
// Build toolbar actions (left: MySpace nav, right: actions)
// ---------------------------------------------------------------------
$actionsLeft = '';

// 0) "View my progress" is always first
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
// 1) Main MySpace navigation with "Teachers" as current section.
$actionsLeft .= Display::mySpaceMenu('teachers');

// 2) Extra action for DRH: skills report.
if (api_is_drh()) {
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

$toolbar = Display::toolbarAction('toolbar-teachers', [$actionsLeft, $actionsRight]);

$table = new SortableTable(
    'tracking_teachers',
    'get_count_users',
    'get_users',
    ($is_western_name_order xor $sort_by_first_name) ? 1 : 0,
    10
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

$table->set_header(2, get_lang('First login in platform'), false);
$table->set_header(3, get_lang('Last connection'), false);
$table->set_header(4, get_lang('Details'), false);

if ($export_csv) {
    if ($is_western_name_order) {
        $csv_header[] = [
            get_lang('First name'),
            get_lang('Last name'),
            get_lang('First login in platform'),
            get_lang('Last connection'),
        ];
    } else {
        $csv_header[] = [
            get_lang('Last name'),
            get_lang('First name'),
            get_lang('First login in platform'),
            get_lang('Last connection'),
        ];
    }
}

$form = new FormValidator(
    'search_user',
    'get',
    $webCodePath.'my_space/teachers.php'
);
$form = Tracking::setUserSearchForm($form);
$form->setDefaults($params);

if ($export_csv) {
    // Send the CSV file if requested.
    $content = $table->get_table_data();
    foreach ($content as &$row) {
        $row[3] = strip_tags($row[3]);
        unset($row[4]);
    }

    $csv_content = array_merge($csv_header, $content);
    ob_end_clean();
    Export::arrayToCsv($csv_content, 'reporting_teacher_list');
    exit;
}

Display::display_header($nameTools);

echo '<style>
    .reporting-teachers-card {
        border-color: #e5e7eb !important;
        border-width: 1px !important;
    }

    .reporting-teachers-card .panel,
    .reporting-teachers-card fieldset {
        border-color: #e5e7eb !important;
    }
</style>';

echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Toolbar row (icons left/right handled by toolbarAction itself).
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Header (title + active/inactive label).
echo '  <div class="flex flex-col gap-3 md:gap-4">';
echo '      <div class="space-y-1">';
echo            Display::page_subheader($nameTools);

if (isset($active)) {
    if ($active) {
        $activeLabel = get_lang('Active users');
    } else {
        $activeLabel = get_lang('Inactive users');
    }

    echo '  <div class="mt-1">';
    echo        Display::page_subheader2($activeLabel);
    echo '  </div>';
}

echo '      </div>';
echo '  </div>';

// Search form card.
echo '  <section class="reporting-teachers-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="p-4 md:p-5">';
$form->display();
echo '      </div>';
echo '  </section>';

// Table card.
echo '  <section class="reporting-teachers-card bg-white rounded-xl shadow-sm w-full">';
echo '      <div class="overflow-x-auto">';
$table->display();
echo '      </div>';
echo '  </section>';

echo '</div>';

Display::display_footer();
