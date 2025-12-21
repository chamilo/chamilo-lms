<?php
/* For licensing terms, see /license.txt */

/**
 * Reporting page on the user's own progress.
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Enums\ToolIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if ('true' === api_get_setting('tracking.block_my_progress_page')) {
    api_not_allowed(true);
}

$httpRequest = Container::getRequest();

$this_section = SECTION_TRACKING;
$nameTools = get_lang('Progress');

$pluginCalendar = 'true' === api_get_plugin_setting('learning_calendar', 'enabled');

if ($pluginCalendar) {
    $plugin = LearningCalendarPlugin::create();
    $plugin->setJavaScript();
}

$user_id = api_get_user_id();
$courseUserList = CourseManager::get_courses_list_by_user_id($user_id);
$dates = $issues = '';

/*
 * Parameter normalization:
 * - Accept both ?session_id= and ?sid=
 * - Accept both ?course=CODE and ?cid=REAL_ID
 *   (internally, tracking.lib expects $_GET['course'] + $_GET['session_id'])
 */

// 1) Normalize session id: prefer session_id, fallback to sid.
$sessionId = $httpRequest->query->getInt('session_id');
if (0 === $sessionId) {
    $sessionId = $httpRequest->query->getInt('sid');
}

// 2) Normalize course: start from "course" (code), or resolve from "cid" (real id).
$courseCode = $httpRequest->query->get('course');
$courseId = 0;

if (empty($courseCode)) {
    $cid = $httpRequest->query->getInt('cid');
    if ($cid > 0) {
        $courseEntity = api_get_course_entity($cid);
        if (null !== $courseEntity) {
            $courseCode = $courseEntity->getCode();
        }
    }
}

if (!empty($courseCode)) {
    $courseInfo = api_get_course_info($courseCode);
    if (!empty($courseInfo)) {
        $courseId = (int) $courseInfo['real_id'];
    }
}

// 3) Ensure tracking.lib can still rely on $_GET['course'] and $_GET['session_id'].
if ($courseCode && !isset($_GET['course'])) {
    $_GET['course'] = $courseCode;
}
if ($sessionId && !isset($_GET['session_id'])) {
    $_GET['session_id'] = $sessionId;
}

// Main progress sections (courses + sessions).
$content = Tracking::show_user_progress($user_id, $sessionId);
$content .= Tracking::show_course_detail($user_id, $courseId, $sessionId);

// Optional messages about the user.
if ('true' === api_get_setting('message.private_messages_about_user_visible_to_user')) {
    $allowMessages = ('true' === api_get_setting('message.private_messages_about_user'));
    if ($allowMessages) {
        $content .= Display::page_subheader2(get_lang('Messages'));
        $content .= MessageManager::getMessagesAboutUserToString(api_get_user_info());
    }
}

$message = null;
if (empty($content)) {
    $message = Display::return_message(get_lang('No data available'), 'warning');
}

// Optional careers section.
$show = ('true' === api_get_setting('session.allow_career_users'));

if ($show) {
    $careers = UserManager::getUserCareers($user_id);

    if (!empty($careers)) {
        $title = Display::page_subheader(get_lang('Careers'), null, 'h3', ['class' => 'section-title']);
        $table = new HTML_Table(['class' => 'data_table']);
        $table->setHeaderContents(0, 0, get_lang('Career'));
        $table->setHeaderContents(0, 1, get_lang('Diagram'));

        $row = 1;
        foreach ($careers as $careerData) {
            $table->setCellContents($row, 0, $careerData['title']);
            $url = api_get_path(WEB_CODE_PATH).'user/career_diagram.php?career_id='.$careerData['id'];
            $diagram = Display::url(get_lang('Diagram'), $url);
            $table->setCellContents($row, 1, $diagram);
            $row++;
        }
        $content = $title.$table->toHtml().$content;
    }
}

// -----------------------------------------------------------------------------
// Toolbar + layout wrapper: keep navigation icons visible and improve spacing.
// -----------------------------------------------------------------------------

$webCodePath = api_get_path(WEB_CODE_PATH);
$actionsLeft = '';
$actionsRight = '';

// First icon: back to main "Follow up" page.
$followUpIcon = Display::getMdiIcon(
    ToolIcon::TRACKING,
    'ch-tool-icon',
    null,
    ICON_SIZE_MEDIUM,
    get_lang('Follow up')
);

// Current page icon: "View my progress" (disabled state).
$myProgressIcon = Display::getMdiIcon(
    ToolIcon::COURSE_PROGRESS,
    'ch-tool-icon-disabled',
    null,
    ICON_SIZE_MEDIUM,
    get_lang('View my progress')
);

if (api_is_drh()) {
    // DRH / managers: full reporting navigation.
    $menuItems = [
        Display::url($followUpIcon, $webCodePath.'my_space/index.php'),
        Display::url($myProgressIcon, '#'),
        Display::url(
            Display::getMdiIcon(
                ObjectIcon::USER,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Learners')
            ),
            $webCodePath.'my_space/student.php'
        ),
        Display::url(
            Display::getMdiIcon(
                ObjectIcon::TEACHER,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Teachers')
            ),
            $webCodePath.'my_space/teachers.php'
        ),
        Display::url(
            Display::getMdiIcon(
                ObjectIcon::COURSE,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Courses')
            ),
            $webCodePath.'my_space/course.php'
        ),
        Display::url(
            Display::getMdiIcon(
                ObjectIcon::SESSION,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Sessions')
            ),
            $webCodePath.'my_space/session.php'
        ),
    ];

    $actionsLeft .= implode('', $menuItems);
} else {
    // Standard users: keep at least Follow up + My progress.
    $actionsLeft .= Display::url($followUpIcon, $webCodePath.'my_space/index.php');
    $actionsLeft .= Display::url($myProgressIcon, '#');

    // Platform admins also get quick access to Courses / Sessions.
    if (api_is_platform_admin(true, true)) {
        $actionsLeft .= Display::url(
            Display::getMdiIcon(
                ObjectIcon::COURSE,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Courses')
            ),
            $webCodePath.'my_space/course.php'
        );
        $actionsLeft .= Display::url(
            Display::getMdiIcon(
                ObjectIcon::SESSION,
                'ch-tool-icon',
                null,
                ICON_SIZE_MEDIUM,
                get_lang('Sessions')
            ),
            $webCodePath.'my_space/session.php'
        );
    }
}

// No right-side actions for now (can be extended later).
$toolbar = Display::toolbarAction('toolbar-my-progress', [$actionsLeft, $actionsRight]);

// Small scoped CSS to add spacing and card-like layout around the tables.
$style = '<style>
.my-progress-wrapper {
    margin-top: 0.75rem;
    max-width: 100%;
    margin-left: auto;
    margin-right: auto;
}

/* Section titles: smaller, compact and aligned with icons */
.my-progress-wrapper h2,
.my-progress-wrapper h3,
.my-progress-wrapper .section-title {
    margin-top: 1.5rem;
    margin-bottom: 0.4rem;
    font-size: 1.25rem;
    line-height: 1.4;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

/* First title closer to toolbar */
.my-progress-wrapper h2:first-of-type,
.my-progress-wrapper h3:first-of-type,
.my-progress-wrapper .section-title:first-of-type {
    margin-top: 1rem;
}

/* Make the main data tables look like white cards with gray borders */
.my-progress-wrapper .table-responsive {
    margin-top: 0.2rem;
    padding: 0.75rem 0.75rem 0.5rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
}

/* Legacy data_table usage (careers, etc.) */
.my-progress-wrapper .data_table {
    margin-top: 0.5rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background-color: #ffffff;
}

/* Compact table headers */
.my-progress-wrapper table thead th {
    padding-top: 0.45rem;
    padding-bottom: 0.45rem;
    font-size: 0.85rem;
    white-space: nowrap;
}

/* Compact table body cells */
.my-progress-wrapper table tbody td {
    padding-top: 0.4rem;
    padding-bottom: 0.4rem;
    font-size: 0.9rem;
}

/* Keep selected/highlighted rows readable inside cards */
.my-progress-wrapper table tr[style*="#FBF09D"] {
    /* no extra rules: inline background is kept, card border wraps it */
}
</style>';

// Wrap original content with toolbar + layout container.
$content =
    $style.
    '<div class="my-progress-wrapper w-full px-4 md:px-8 pb-8 space-y-4">'.
    '  <div class="flex justify-start md:justify-end mb-2">'.$toolbar.'</div>'.
    '  <div class="mt-2">'.$content.'</div>'.
    '</div>';

$tpl = new Template($nameTools);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
