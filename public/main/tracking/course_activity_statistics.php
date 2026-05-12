<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

require_once __DIR__.'/../inc/global.inc.php';
require_once __DIR__.'/../inc/lib/reports.lib.php';

$current_course_tool = TOOL_TRACKING;
$this_section = SECTION_COURSES;

$course = api_get_course_entity();
if (null === $course) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();
$courseId = (int) $course->getId();
$courseCode = $course->getCode();
$sessionId = (int) api_get_session_id();
$groupId = (int) api_get_group_id();

if (!Tracking::isAllowToTrack($sessionId)) {
    api_not_allowed(true);
}

$nameTools = get_lang('Course activity statistics');

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq(),
    'name' => get_lang('Report on learners'),
];

$now = new DateTimeImmutable();
$since24Hours = $now->modify('-24 hours');
$since7Days = $now->modify('-7 days');
$since30Days = $now->modify('-30 days');

$resetDateInput = isset($_GET['tracking_start_date'])
    ? trim((string) $_GET['tracking_start_date'])
    : $since30Days->format('Y-m-d');

$resetDate = parseTrackingDate($resetDateInput, $since30Days);
$resetDateInput = $resetDate->format('Y-m-d');

if (empty($sessionId)) {
    $students = CourseManager::get_student_list_from_course_code(
        api_get_course_id(),
        false,
        0,
        null,
        null,
        true,
        $groupId
    );
} else {
    $students = CourseManager::get_student_list_from_course_code(
        api_get_course_id(),
        true,
        $sessionId
    );
}

$studentIds = getStudentIdsFromCourseList($students);
$totalStudents = count($studentIds);

$connected24Hours = TrackingCourseLog::getCourseConnectedUsersSince($courseId, $sessionId, $since24Hours, $studentIds);
$connected7Days = TrackingCourseLog::getCourseConnectedUsersSince($courseId, $sessionId, $since7Days, $studentIds);
$connected30Days = TrackingCourseLog::getCourseConnectedUsersSince($courseId, $sessionId, $since30Days, $studentIds);
$connectedSinceReset = TrackingCourseLog::getCourseConnectedUsersSince($courseId, $sessionId, $resetDate, $studentIds);
$notRecentlyConnected = TrackingCourseLog::getCourseUsersNotConnectedSince($students, $courseId, $sessionId, $resetDate);
$resourceUsage = TrackingCourseLog::getCourseResourceUsageSummarySince($courseId, $sessionId, $resetDate);

Display::display_header($nameTools, 'Tracking');

echo ReportRegistry::renderReportActionBar(
    'course_activity_statistics',
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq()
);

$actionsLeft = TrackingCourseLog::actionsLeft('activity', $sessionId, false);
$actionsRight = Display::url(
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq(),
    ['title' => get_lang('Back')]
);

echo Display::toolbarAction(
    'course-activity-statistics',
    [$actionsLeft, $actionsRight]
);

echo '<div class="w-full px-4 md:px-8 pb-8 space-y-5">';

echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
echo '<div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">';
echo '<div>';
echo '<h2 class="text-2xl font-semibold mb-2">'.Security::remove_XSS($nameTools).'</h2>';
echo '<div class="text-sm text-gray-600">';
echo '<div><strong>'.get_lang('Course').':</strong> '.Security::remove_XSS($courseInfo['name'] ?? $courseCode).'</div>';
if ($sessionId > 0) {
    $sessionInfo = api_get_session_info($sessionId);
    echo '<div><strong>'.get_lang('Session').':</strong> '.Security::remove_XSS($sessionInfo['name'] ?? (string) $sessionId).'</div>';
}
echo '</div>';
echo '</div>';
echo '<div class="text-sm text-gray-600 md:text-right">';
echo '<div><strong>'.get_lang('Learners').':</strong> '.$totalStudents.'</div>';
echo '<div><strong>'.get_lang('Statistics reset date').':</strong> '.Security::remove_XSS(api_get_local_time($resetDate->format('Y-m-d H:i:s'))).'</div>';
echo '</div>';
echo '</div>';
echo '</section>';

echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
echo '<form method="get" action="'.Security::remove_XSS(api_get_self()).'" class="flex flex-col gap-3 md:flex-row md:items-end">';
echo '<input type="hidden" name="cid" value="'.$courseId.'">';
echo '<input type="hidden" name="sid" value="'.$sessionId.'">';
echo '<input type="hidden" name="gid" value="'.$groupId.'">';
echo '<input type="hidden" name="gradebook" value="'.(int) ($_GET['gradebook'] ?? 0).'">';
echo '<input type="hidden" name="origin" value="'.Security::remove_XSS((string) ($_GET['origin'] ?? '')).'">';
echo '<div class="flex flex-col">';
echo '<label for="tracking_start_date" class="text-sm font-semibold text-gray-800 mb-1">'.get_lang('Statistics reset date').'</label>';
echo '<input id="tracking_start_date" name="tracking_start_date" type="date" value="'.Security::remove_XSS($resetDateInput).'" class="form-control">';
echo '</div>';
echo '<button type="submit" class="btn btn--primary">'.get_lang('Apply').'</button>';
echo '<div class="flex flex-wrap gap-2 text-sm">';
echo Display::url(get_lang('Last 24 hours'), buildCourseActivityUrl($since24Hours), ['class' => 'btn btn--plain']);
echo Display::url(get_lang('Last week'), buildCourseActivityUrl($since7Days), ['class' => 'btn btn--plain']);
echo Display::url(get_lang('Last month'), buildCourseActivityUrl($since30Days), ['class' => 'btn btn--plain']);
echo Display::url(get_lang('Today'), buildCourseActivityUrl(new DateTimeImmutable('today')), ['class' => 'btn btn--plain']);
echo '</div>';
echo '</form>';
echo '<p class="m-0 mt-3 text-sm text-gray-600">';
echo get_lang('This reset date is used as the reference date for the report. It does not delete historical tracking data.');
echo '</p>';
echo '</section>';

echo '<section class="grid gap-4 md:grid-cols-4">';
echo renderActivityCard(get_lang('Last 24 hours'), count($connected24Hours), $totalStudents);
echo renderActivityCard(get_lang('Last week'), count($connected7Days), $totalStudents);
echo renderActivityCard(get_lang('Last month'), count($connected30Days), $totalStudents);
echo renderActivityCard(get_lang('Since reset date'), count($connectedSinceReset), $totalStudents);
echo '</section>';

echo renderTrackingSection(
    get_lang('Users connected in the last 24 hours'),
    get_lang('Learners with at least one course access during the last 24 hours.'),
    renderConnectedUsersTable($connected24Hours)
);

echo renderTrackingSection(
    get_lang('Users connected in the last week'),
    get_lang('Learners with at least one course access during the last 7 days.'),
    renderConnectedUsersTable($connected7Days)
);

echo renderTrackingSection(
    get_lang('Users connected in the last month'),
    get_lang('Learners with at least one course access during the last 30 days.'),
    renderConnectedUsersTable($connected30Days)
);

echo renderTrackingSection(
    get_lang('Not recently connected users'),
    sprintf(
        get_lang('Learners without course access since %s.'),
        Security::remove_XSS(api_get_local_time($resetDate->format('Y-m-d H:i:s')))
    ),
    renderInactiveUsersTable($notRecentlyConnected)
);

echo renderTrackingSection(
    get_lang('Recently used resources'),
    sprintf(
        get_lang('Course tools/resources used since %s.'),
        Security::remove_XSS(api_get_local_time($resetDate->format('Y-m-d H:i:s')))
    ),
    renderResourceUsageTable($resourceUsage)
);

echo '</div>';

Display::display_footer();

function parseTrackingDate(string $date, DateTimeImmutable $default): DateTimeImmutable
{
    if ('' === $date) {
        return $default->setTime(0, 0, 0);
    }

    $parsed = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00');
    if (!$parsed instanceof DateTimeImmutable) {
        return $default->setTime(0, 0, 0);
    }

    return $parsed;
}

function buildCourseActivityUrl(DateTimeImmutable $date): string
{
    $params = [
        'cid' => (int) api_get_course_int_id(),
        'sid' => (int) api_get_session_id(),
        'gid' => (int) api_get_group_id(),
        'gradebook' => (int) ($_GET['gradebook'] ?? 0),
        'origin' => (string) ($_GET['origin'] ?? ''),
        'tracking_start_date' => $date->format('Y-m-d'),
    ];

    return api_get_path(WEB_CODE_PATH).'tracking/course_activity_statistics.php?'.http_build_query($params);
}

/**
 * @param array<int|string,array<string,mixed>> $students
 *
 * @return int[]
 */
function getStudentIdsFromCourseList(array $students): array
{
    $ids = [];

    foreach ($students as $key => $student) {
        if (!is_array($student)) {
            continue;
        }

        $userId = (int) ($student['user_id'] ?? $student['id'] ?? $key);
        if ($userId > 0) {
            $ids[] = $userId;
        }
    }

    return array_values(array_unique($ids));
}

function renderActivityCard(string $title, int $count, int $total): string
{
    $percentage = $total > 0 ? round($count * 100 / $total, 1) : 0;

    return '<article class="bg-white rounded-xl shadow-sm border border-gray-50 p-4">'.
        '<div class="text-sm font-semibold text-gray-600">'.Security::remove_XSS($title).'</div>'.
        '<div class="mt-2 text-3xl font-bold text-gray-900">'.$count.'</div>'.
        '<div class="mt-1 text-sm text-gray-600">'.sprintf(get_lang('%s %% of learners'), $percentage).'</div>'.
        '</article>';
}

function renderTrackingSection(string $title, string $description, string $content): string
{
    return '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">'.
        '<div class="mb-4">'.
        '<h3 class="text-xl font-semibold mb-1">'.Security::remove_XSS($title).'</h3>'.
        '<p class="m-0 text-sm text-gray-600">'.$description.'</p>'.
        '</div>'.
        $content.
        '</section>';
}

/**
 * @param array<int,array<string,mixed>> $rows
 */
function renderConnectedUsersTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No results found'), 'info');
    }

    $html = '<div class="overflow-x-auto">';
    $html .= '<table class="table data_table w-full">';
    $html .= '<thead><tr>'.
        '<th>'.get_lang('Learner').'</th>'.
        '<th>'.get_lang('Username').'</th>'.
        '<th>'.get_lang('Connections').'</th>'.
        '<th>'.get_lang('First connection').'</th>'.
        '<th>'.get_lang('Last connection').'</th>'.
        '<th>'.get_lang('Total time').'</th>'.
        '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $name = api_get_person_name((string) ($row['firstname'] ?? ''), (string) ($row['lastname'] ?? ''));
        $name = trim($name) ?: ('#'.(int) ($row['user_id'] ?? 0));

        $html .= '<tr>'.
            '<td>'.Security::remove_XSS($name).'</td>'.
            '<td>'.Security::remove_XSS((string) ($row['username'] ?? '')).'</td>'.
            '<td class="text-right">'.(int) ($row['connection_count'] ?? 0).'</td>'.
            '<td>'.formatTrackingDate($row['first_access'] ?? null).'</td>'.
            '<td>'.formatTrackingDate($row['last_access'] ?? null).'</td>'.
            '<td>'.api_time_to_hms((int) ($row['total_seconds'] ?? 0)).'</td>'.
            '</tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
}

/**
 * @param array<int,array<string,mixed>> $rows
 */
function renderInactiveUsersTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No results found'), 'success');
    }

    $html = '<div class="overflow-x-auto">';
    $html .= '<table class="table data_table w-full">';
    $html .= '<thead><tr>'.
        '<th>'.get_lang('Learner').'</th>'.
        '<th>'.get_lang('Username').'</th>'.
        '<th>'.get_lang('E-mail').'</th>'.
        '<th>'.get_lang('Last connection').'</th>'.
        '<th>'.get_lang('Total connections').'</th>'.
        '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $name = api_get_person_name((string) ($row['firstname'] ?? ''), (string) ($row['lastname'] ?? ''));
        $name = trim($name) ?: ('#'.(int) ($row['user_id'] ?? 0));

        $html .= '<tr>'.
            '<td>'.Security::remove_XSS($name).'</td>'.
            '<td>'.Security::remove_XSS((string) ($row['username'] ?? '')).'</td>'.
            '<td>'.Security::remove_XSS((string) ($row['email'] ?? '')).'</td>'.
            '<td>'.formatTrackingDate($row['last_access'] ?? null).'</td>'.
            '<td class="text-right">'.(int) ($row['connection_count'] ?? 0).'</td>'.
            '</tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
}

/**
 * @param array<int,array<string,mixed>> $rows
 */
function renderResourceUsageTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No results found'), 'info');
    }

    $html = '<div class="overflow-x-auto">';
    $html .= '<table class="table data_table w-full">';
    $html .= '<thead><tr>'.
        '<th>'.get_lang('Tool').'</th>'.
        '<th>'.get_lang('Events').'</th>'.
        '<th>'.get_lang('Users').'</th>'.
        '<th>'.get_lang('Last access').'</th>'.
        '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $tool = (string) ($row['access_tool'] ?? '');
        $html .= '<tr>'.
            '<td>'.Security::remove_XSS($tool ?: get_lang('Unknown')).'</td>'.
            '<td class="text-right">'.(int) ($row['event_count'] ?? 0).'</td>'.
            '<td class="text-right">'.(int) ($row['user_count'] ?? 0).'</td>'.
            '<td>'.formatTrackingDate($row['last_access'] ?? null).'</td>'.
            '</tr>';
    }

    $html .= '</tbody></table></div>';

    return $html;
}

function formatTrackingDate($date): string
{
    if (empty($date)) {
        return '-';
    }

    return Security::remove_XSS(api_get_local_time((string) $date));
}
