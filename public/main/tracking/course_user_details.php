<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

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
$studentId = isset($_GET['student']) ? (int) $_GET['student'] : 0;

if ($studentId <= 0) {
    api_not_allowed(true);
}

if (!Tracking::isAllowToTrack($sessionId)) {
    api_not_allowed(true);
}

$userInfo = api_get_user_info($studentId);
if (empty($userInfo)) {
    api_not_allowed(true);
}

$nameTools = get_lang('Course tracking details');

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq(),
    'name' => get_lang('Report on learners'),
];

Display::display_header($nameTools, 'Tracking');

echo ReportRegistry::renderReportActionBar(
    'course_learner_tracking_details',
    api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq()
);

$backUrl = api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?'.api_get_cidreq();
$legacyDetailsUrl = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true'
    .'&cid='.$courseId
    .'&course='.$courseCode
    .'&origin=tracking_course'
    .'&sid='.$sessionId
    .'&student='.$studentId;

$downloads = TrackingCourseLog::getCourseUserDownloadedDocuments($studentId, $courseId, $sessionId, 200);
$forumThreads = TrackingCourseLog::getCourseUserForumThreads($studentId, $courseId, $sessionId, 200);
$forumPosts = TrackingCourseLog::getCourseUserForumPosts($studentId, $courseId, $sessionId, 200);
$courseAccessList = TrackingCourseLog::getCourseUserAccessList($studentId, $courseId, $sessionId, 200);
$resourceAccessList = TrackingCourseLog::getCourseUserResourceAccessList($studentId, $courseId, $sessionId, 200);

$studentName = api_get_person_name($userInfo['firstname'] ?? '', $userInfo['lastname'] ?? '');
$studentName = trim($studentName) ?: ($userInfo['username'] ?? ('#'.$studentId));

$toolbar = Display::toolbarAction(
    'course-user-tracking-details',
    [
        Display::toolbarButton(
            get_lang('Back'),
            $backUrl,
            ActionIcon::BACK,
            'primary'
        ),
        Display::toolbarButton(
            get_lang('Learner details'),
            $legacyDetailsUrl,
            'chart-box-outline',
            'plain'
        ),
    ]
);

echo '<div class="w-full px-4 md:px-8 pb-8 space-y-4">';
echo '<div class="flex flex-wrap gap-2">'.$toolbar.'</div>';

echo '<section class="bg-white rounded-xl shadow-sm border border-gray-50 p-4 md:p-5">';
echo '<h2 class="text-2xl font-semibold mb-2">'.Security::remove_XSS($studentName).'</h2>';
echo '<div class="grid gap-2 md:grid-cols-2 text-sm">';
echo '<div><strong>'.get_lang('Course').':</strong> '.Security::remove_XSS($courseInfo['name'] ?? $courseCode).'</div>';
echo '<div><strong>'.get_lang('Username').':</strong> '.Security::remove_XSS($userInfo['username'] ?? '').'</div>';
if ($sessionId > 0) {
    $sessionInfo = api_get_session_info($sessionId);
    echo '<div><strong>'.get_lang('Session').':</strong> '.Security::remove_XSS($sessionInfo['name'] ?? (string) $sessionId).'</div>';
}
echo '</div>';
echo '</section>';

echo renderTrackingSection(
    get_lang('Downloaded documents'),
    get_lang('List of documents downloaded by this learner in this course.'),
    renderDownloadsTable($downloads)
);

echo renderTrackingSection(
    get_lang('Forum topics'),
    get_lang('Forum topics opened by this learner in this course.'),
    renderForumThreadsTable($forumThreads)
);

echo renderTrackingSection(
    get_lang('Forum posts'),
    get_lang('Forum posts written by this learner in this course.'),
    renderForumPostsTable($forumPosts)
);

echo renderTrackingSection(
    get_lang('Course connections'),
    get_lang('Connection history for this learner in this course.'),
    renderCourseAccessTable($courseAccessList)
);

echo renderTrackingSection(
    get_lang('Resources used'),
    get_lang('Latest tools or resources used by this learner in this course.'),
    renderResourceAccessTable($resourceAccessList)
);

echo '</div>';

Display::display_footer();

function renderTrackingSection(string $title, string $description, string $content): string
{
    return '<section class="bg-white rounded-xl shadow-sm border border-gray-50 overflow-hidden">'
        .'<div class="p-4 md:p-5 border-b border-gray-25">'
        .'<h3 class="text-lg font-semibold m-0">'.Security::remove_XSS($title).'</h3>'
        .'<p class="text-sm text-gray-600 mt-1 mb-0">'.Security::remove_XSS($description).'</p>'
        .'</div>'
        .'<div class="p-4 md:p-5 overflow-x-auto">'.$content.'</div>'
        .'</section>';
}

function renderDownloadsTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No data available'), 'info', false);
    }

    $html = '<table class="data_table"><thead><tr>'
        .'<th>'.get_lang('Document').'</th>'
        .'<th>'.get_lang('Path').'</th>'
        .'<th>'.get_lang('Date').'</th>'
        .'</tr></thead><tbody>';

    foreach ($rows as $row) {
        $title = $row['document_title'] ?: ($row['resource_title'] ?: $row['down_doc_path']);
        $documentId = (int) ($row['document_id'] ?? 0);
        $titleHtml = Security::remove_XSS((string) $title);

        if ($documentId > 0) {
            $url = '/resources/document/'.$documentId.'?'.api_get_cidreq();
            $titleHtml = Display::url($titleHtml, $url);
        }

        $html .= '<tr>'
            .'<td>'.$titleHtml.'</td>'
            .'<td>'.Security::remove_XSS((string) ($row['resource_path'] ?: $row['down_doc_path'])).'</td>'
            .'<td>'.formatTrackingDate($row['down_date'] ?? null).'</td>'
            .'</tr>';
    }

    return $html.'</tbody></table>';
}

function renderForumThreadsTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No data available'), 'info', false);
    }

    $html = '<table class="data_table"><thead><tr>'
        .'<th>'.get_lang('Topic').'</th>'
        .'<th>'.get_lang('Forum').'</th>'
        .'<th>'.get_lang('Replies').'</th>'
        .'<th>'.get_lang('Views').'</th>'
        .'<th>'.get_lang('Date').'</th>'
        .'</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>'
            .'<td>'.renderForumThreadLink($row).'</td>'
            .'<td>'.Security::remove_XSS((string) ($row['forum_title'] ?? '')).'</td>'
            .'<td>'.(int) ($row['thread_replies'] ?? 0).'</td>'
            .'<td>'.(int) ($row['thread_views'] ?? 0).'</td>'
            .'<td>'.formatTrackingDate($row['thread_date'] ?? null).'</td>'
            .'</tr>';
    }

    return $html.'</tbody></table>';
}

function renderForumPostsTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No data available'), 'info', false);
    }

    $html = '<table class="data_table"><thead><tr>'
        .'<th>'.get_lang('Post').'</th>'
        .'<th>'.get_lang('Topic').'</th>'
        .'<th>'.get_lang('Forum').'</th>'
        .'<th>'.get_lang('Date').'</th>'
        .'</tr></thead><tbody>';

    foreach ($rows as $row) {
        $postTitle = $row['post_title'] ?: $row['thread_title'];
        $postTitle = Security::remove_XSS((string) $postTitle);
        $threadLink = renderForumThreadLink($row);
        $postId = (int) ($row['post_id'] ?? 0);

        if ($postId > 0 && !empty($threadLink)) {
            $postTitle = Display::url(
                $postTitle,
                buildForumThreadUrl($row).'#post'.$postId
            );
        }

        $html .= '<tr>'
            .'<td>'.$postTitle.'</td>'
            .'<td>'.$threadLink.'</td>'
            .'<td>'.Security::remove_XSS((string) ($row['forum_title'] ?? '')).'</td>'
            .'<td>'.formatTrackingDate($row['post_date'] ?? null).'</td>'
            .'</tr>';
    }

    return $html.'</tbody></table>';
}

function renderCourseAccessTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No data available'), 'info', false);
    }

    $html = '<table class="data_table"><thead><tr>'
        .'<th>'.get_lang('Login date').'</th>'
        .'<th>'.get_lang('Logout date').'</th>'
        .'<th>'.get_lang('Time').'</th>'
        .'<th>'.get_lang('Visits').'</th>'
        .'<th>'.get_lang('IP address').'</th>'
        .'</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>'
            .'<td>'.formatTrackingDate($row['login_course_date'] ?? null).'</td>'
            .'<td>'.formatTrackingDate($row['logout_course_date'] ?? null).'</td>'
            .'<td>'.formatTrackingDuration($row['login_course_date'] ?? null, $row['logout_course_date'] ?? null).'</td>'
            .'<td>'.(int) ($row['counter'] ?? 0).'</td>'
            .'<td>'.Security::remove_XSS((string) ($row['user_ip'] ?? '')).'</td>'
            .'</tr>';
    }

    return $html.'</tbody></table>';
}

function renderResourceAccessTable(array $rows): string
{
    if (empty($rows)) {
        return Display::return_message(get_lang('No data available'), 'info', false);
    }

    $html = '<table class="data_table"><thead><tr>'
        .'<th>'.get_lang('Tool').'</th>'
        .'<th>'.get_lang('Date').'</th>'
        .'<th>'.get_lang('IP address').'</th>'
        .'</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>'
            .'<td>'.Security::remove_XSS((string) ($row['access_tool'] ?? '')).'</td>'
            .'<td>'.formatTrackingDate($row['access_date'] ?? null).'</td>'
            .'<td>'.Security::remove_XSS((string) ($row['user_ip'] ?? '')).'</td>'
            .'</tr>';
    }

    return $html.'</tbody></table>';
}

function renderForumThreadLink(array $row): string
{
    $threadTitle = Security::remove_XSS((string) ($row['thread_title'] ?? ''));
    if (empty($threadTitle)) {
        return '';
    }

    return Display::url($threadTitle, buildForumThreadUrl($row));
}

function buildForumThreadUrl(array $row): string
{
    $forumId = (int) ($row['forum_id'] ?? 0);
    $threadId = (int) ($row['thread_id'] ?? 0);

    return api_get_path(WEB_CODE_PATH).'forum/viewthread.php?'
        .api_get_cidreq()
        .'&forum='.$forumId
        .'&thread='.$threadId;
}

function formatTrackingDate($value): string
{
    if (empty($value)) {
        return '-';
    }

    return Security::remove_XSS(api_get_local_time((string) $value));
}

function formatTrackingDuration($start, $end): string
{
    if (empty($start) || empty($end)) {
        return '-';
    }

    $startTime = strtotime((string) $start);
    $endTime = strtotime((string) $end);

    if (!$startTime || !$endTime || $endTime < $startTime) {
        return '-';
    }

    return api_time_to_hms($endTime - $startTime);
}
